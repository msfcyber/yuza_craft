import * as THREE from 'three';
import { unzipSync } from 'three/addons/libs/fflate.module.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';

function startViewer(container) {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color('#e8e7df');
    const camera = new THREE.PerspectiveCamera(40, 1, 0.01, 100);
    camera.position.set(2.8, 2.2, 3.3);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    container.prepend(renderer.domElement);

    scene.add(new THREE.HemisphereLight(0xffffff, 0x8d8b7d, 2.1));
    const keyLight = new THREE.DirectionalLight(0xffffff, 3.2);
    keyLight.position.set(4, 7, 5);
    scene.add(keyLight);
    const fillLight = new THREE.DirectionalLight(0xffffff, 1.2);
    fillLight.position.set(-4, 2, -3);
    scene.add(fillLight);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.07;
    controls.minDistance = 1.5;
    controls.maxDistance = 8;
    controls.maxPolarAngle = Math.PI * 0.88;

    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(2.5, 64),
        new THREE.MeshBasicMaterial({ color: '#d5d3ca', transparent: true, opacity: 0.55 }),
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -1.02;
    scene.add(floor);

    const resize = () => {
        const width = container.clientWidth;
        const height = container.clientHeight;
        if (!width || !height) return;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    };
    const observer = new ResizeObserver(resize);
    observer.observe(container);
    resize();

    let activeModel;
    let keycapModel;
    let keycapTemplate;
    let keycapTemplateBounds;
    let keycapButtonBounds;
    let keycapNameBounds;
    let keycapTemplateUnitWidth = 0;
    const geometrySmallShells = new WeakMap();
    let customName = container.dataset.customName || '';
    const keycapGenerator = container.dataset.keycapGenerator === 'true';
    const format = container.dataset.modelFormat?.toLowerCase();
    const componentColors = {
        base: container.dataset.baseColor || container.dataset.color || '#c9b896',
        button: container.dataset.buttonColor || container.dataset.color || '#c9b896',
        name: container.dataset.nameColor || container.dataset.color || '#c9b896',
    };

    const getComponent = (object, root = activeModel) => {
        let current = object;
        while (current && current !== root?.parent) {
            const name = (current.name || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            if (name.includes('button') || name.includes('tombol')) return 'button';
            if (name.includes('name') || name.includes('text') || name.includes('tulisan') || name.includes('huruf') || name.includes('letter') || name.includes('glyph')) return 'name';
            if (name.includes('base') || name.includes('body')) return 'base';
            current = current.parent;
        }
        return null;
    };

    const applyColor = (hex) => {
        if (!activeModel) return;
        activeModel.traverse((child) => {
            if (!child.isMesh) return;
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                material.color?.set(hex);
                material.needsUpdate = true;
            });
        });
    };

    const disposeGeneratedModel = () => {
        if (!keycapModel) return;

        scene.remove(keycapModel);
        keycapModel.traverse((child) => {
            if (!child.isMesh) return;

            if (child.userData.keycapGeneratedGeometry) {
                child.geometry.dispose();
            }
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                if (material.userData.keycapGeneratedTexture) {
                    material.map?.dispose();
                }
                material.dispose();
            });
        });
        keycapModel = null;
    };

    const createRaisedGlyphGeometry = (character, maxWidth) => {
        const canvas = document.createElement('canvas');
        canvas.width = 160;
        canvas.height = 160;
        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.font = '700 140px system-ui, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(character, canvas.width / 2, canvas.height / 2, 148);
        const { data } = context.getImageData(0, 0, canvas.width, canvas.height);
        let minX = canvas.width;
        let minY = canvas.height;
        let maxX = -1;
        let maxY = -1;

        for (let y = 0; y < canvas.height; y += 1) {
            for (let x = 0; x < canvas.width; x += 1) {
                if (data[(y * canvas.width + x) * 4 + 3] < 32) continue;
                minX = Math.min(minX, x);
                minY = Math.min(minY, y);
                maxX = Math.max(maxX, x);
                maxY = Math.max(maxY, y);
            }
        }

        const geometry = new THREE.BufferGeometry();
        if (maxX < minX || maxY < minY) return geometry;

        const scale = maxWidth / (maxX - minX + 1);
        const thickness = maxWidth * 0.05;
        const positions = [];
        const addFace = (first, second, third, fourth) => {
            positions.push(...first, ...second, ...third, ...first, ...third, ...fourth);
        };

        for (let y = minY; y <= maxY; y += 1) {
            let runStart = -1;

            const addRun = (runEnd) => {
                const x0 = (runStart - minX) * scale - (maxX - minX + 1) * scale / 2;
                const x1 = (runEnd + 1 - minX) * scale - (maxX - minX + 1) * scale / 2;
                const y0 = ((maxY - y) - (maxY - minY + 1) / 2) * scale;
                const y1 = y0 + scale;
                const back = 0;
                const front = thickness;

                addFace([x0, y0, front], [x1, y0, front], [x1, y1, front], [x0, y1, front]);
                addFace([x0, y1, back], [x1, y1, back], [x1, y0, back], [x0, y0, back]);
                addFace([x1, y0, back], [x1, y1, back], [x1, y1, front], [x1, y0, front]);
                addFace([x0, y1, back], [x0, y0, back], [x0, y0, front], [x0, y1, front]);
                addFace([x0, y1, back], [x0, y1, front], [x1, y1, front], [x1, y1, back]);
                addFace([x0, y0, back], [x1, y0, back], [x1, y0, front], [x0, y0, front]);
            };

            for (let x = minX; x <= maxX + 1; x += 1) {
                const isGlyphPixel = x <= maxX && data[(y * canvas.width + x) * 4 + 3] >= 32;

                if (isGlyphPixel && runStart === -1) {
                    runStart = x;
                } else if (!isGlyphPixel && runStart !== -1) {
                    addRun(x - 1);
                    runStart = -1;
                }
            }
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.computeVertexNormals();
        geometry.userData.keycapGeneratedGeometry = true;

        return geometry;
    };

    const createRaisedGlyph = (character, maxWidth, color) => {
        const glyph = new THREE.Mesh(
            createRaisedGlyphGeometry(character, maxWidth),
            new THREE.MeshStandardMaterial({ color, roughness: 0.38 }),
        );
        glyph.name = 'dynamic-name-glyph';
        glyph.rotation.x = -Math.PI / 2;
        glyph.castShadow = true;
        glyph.receiveShadow = true;

        return glyph;
    };

    const getSmallestConnectedShell = (geometry) => {
        if (geometrySmallShells.has(geometry)) {
            return geometrySmallShells.get(geometry);
        }
        const sourceIndex = geometry.index;
        if (!sourceIndex || sourceIndex.count < 6) {
            geometrySmallShells.set(geometry, null);

            return null;
        }

        const vertexCount = geometry.getAttribute('position').count;
        const parents = new Uint32Array(vertexCount);
        parents.forEach((_, index) => {
            parents[index] = index;
        });

        const findRoot = (vertexIndex) => {
            let root = vertexIndex;
            while (parents[root] !== root) {
                parents[root] = parents[parents[root]];
                root = parents[root];
            }

            return root;
        };

        const joinVertices = (firstIndex, secondIndex) => {
            const firstRoot = findRoot(firstIndex);
            const secondRoot = findRoot(secondIndex);

            if (firstRoot !== secondRoot) {
                parents[firstRoot] = secondRoot;
            }
        };

        for (let index = 0; index < sourceIndex.count; index += 3) {
            const first = sourceIndex.getX(index);
            const second = sourceIndex.getX(index + 1);
            const third = sourceIndex.getX(index + 2);
            joinVertices(first, second);
            joinVertices(second, third);
        }

        const componentFaces = new Map();
        for (let index = 0; index < sourceIndex.count; index += 3) {
            const root = findRoot(sourceIndex.getX(index));
            componentFaces.set(root, (componentFaces.get(root) ?? 0) + 1);
        }

        const components = [...componentFaces.entries()].sort((first, second) => first[1] - second[1]);
        if (components.length < 2 || components[0][1] >= components.at(-1)[1] * 0.4) {
            geometrySmallShells.set(geometry, null);

            return null;
        }

        const smallRoot = components[0][0];
        const vertexIndexes = [];
        const triangleIndexes = [];
        for (let vertexIndex = 0; vertexIndex < vertexCount; vertexIndex += 1) {
            if (findRoot(vertexIndex) === smallRoot) {
                vertexIndexes.push(vertexIndex);
            }
        }

        for (let index = 0; index < sourceIndex.count; index += 3) {
            if (findRoot(sourceIndex.getX(index)) === smallRoot) {
                triangleIndexes.push(index);
            }
        }

        const shell = { vertexIndexes, triangleIndexes };
        geometrySmallShells.set(geometry, shell);

        return shell;
    };

    const splitHangerFromBaseGeometry = (geometry) => {
        const shell = getSmallestConnectedShell(geometry);
        if (!shell) return null;

        const sourceIndex = geometry.index;
        const smallShellTriangles = new Set(shell.triangleIndexes);
        const bodyIndices = [];
        const hangerIndices = [];
        for (let index = 0; index < sourceIndex.count; index += 3) {
            const target = smallShellTriangles.has(index) ? hangerIndices : bodyIndices;
            target.push(sourceIndex.getX(index), sourceIndex.getX(index + 1), sourceIndex.getX(index + 2));
        }

        const createGeometry = (indices, mirroredToLeft = false) => {
            const splitGeometry = geometry.clone();
            splitGeometry.setIndex(indices);
            splitGeometry.clearGroups();

            if (mirroredToLeft) {
                const positions = splitGeometry.getAttribute('position');
                if (!geometry.boundingBox) {
                    geometry.computeBoundingBox();
                }
                const centerX = (geometry.boundingBox.min.x + geometry.boundingBox.max.x) / 2;

                shell.vertexIndexes.forEach((vertexIndex) => {
                    positions.setX(vertexIndex, centerX * 2 - positions.getX(vertexIndex));
                });
                positions.needsUpdate = true;

                const index = splitGeometry.index;
                for (let offset = 0; offset < index.count; offset += 3) {
                    const second = index.getX(offset + 1);
                    index.setX(offset + 1, index.getX(offset + 2));
                    index.setX(offset + 2, second);
                }
                index.needsUpdate = true;
            }

            splitGeometry.computeVertexNormals();
            const bounds = new THREE.Box3();
            const positions = splitGeometry.getAttribute('position');
            indices.forEach((vertexIndex) => {
                bounds.expandByPoint(new THREE.Vector3().fromBufferAttribute(positions, vertexIndex));
            });
            splitGeometry.boundingBox = bounds;
            splitGeometry.boundingSphere = bounds.getBoundingSphere(new THREE.Sphere());

            return splitGeometry;
        };

        return {
            body: createGeometry(bodyIndices),
            hanger: createGeometry(hangerIndices, true),
        };
    };

    const cloneKeycapTemplate = (character, keepsHanger) => {
        const key = new THREE.Group();
        key.name = `generated-keycap-${character}`;
        const model = keycapTemplate.clone(true);
        model.traverse((child) => {
            if (child.userData.keycapHanger) {
                child.visible = keepsHanger;
            }

            const component = getComponent(child, model);

            if (component === 'name') {
                child.visible = false;
                return;
            }

            if (!child.isMesh) return;

            child.castShadow = true;
            child.receiveShadow = true;
            child.material = Array.isArray(child.material)
                ? child.material.map((material) => material.clone())
                : child.material.clone();

            if (!component) return;

            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                material.color?.set(componentColors[component]);
                material.needsUpdate = true;
            });
        });
        key.add(model);

        const buttonBounds = keycapButtonBounds || keycapTemplateBounds;
        const buttonSize = buttonBounds.getSize(new THREE.Vector3());
        const glyphWidth = Math.min(buttonSize.x, buttonSize.z) * 0.38;
        const labelCenter = (keycapNameBounds || buttonBounds).getCenter(new THREE.Vector3());
        const glyph = createRaisedGlyph(character, glyphWidth, componentColors.name);
        glyph.position.set(labelCenter.x, buttonBounds.max.y + 0.004, labelCenter.z);
        key.add(glyph);

        return key;
    };

    const prepareKeycapTemplate = (template) => {
        keycapTemplate = new THREE.Group();
        keycapTemplate.name = 'keycap-template';
        template.rotation.x = -Math.PI / 2;
        keycapTemplate.add(template);
        keycapTemplate.updateMatrixWorld(true);

        const attachmentCandidateMeshes = [];
        keycapTemplate.traverse((child) => {
            if (child.isMesh && getComponent(child, keycapTemplate) !== 'name') {
                attachmentCandidateMeshes.push(child);
            }
        });
        attachmentCandidateMeshes.forEach((baseMesh) => {
            const splitGeometry = splitHangerFromBaseGeometry(baseMesh.geometry);
            if (!splitGeometry) return;

            baseMesh.geometry = splitGeometry.body;
            const hangerMesh = baseMesh.clone(false);
            hangerMesh.name = 'hanger-attachment';
            hangerMesh.geometry = splitGeometry.hanger;
            hangerMesh.userData.keycapHanger = true;
            baseMesh.parent.add(hangerMesh);
        });
        keycapTemplate.updateMatrixWorld(true);

        const initialBounds = new THREE.Box3();
        keycapTemplate.traverse((child) => {
            if (child.isMesh && ! child.userData.keycapHanger) {
                initialBounds.expandByObject(child);
            }
        });
        const initialSize = initialBounds.getSize(new THREE.Vector3());
        const center = initialBounds.getCenter(new THREE.Vector3());
        const initialWidth = Math.max(initialSize.x, initialSize.z);

        if (!initialWidth) {
            keycapTemplate = null;
            keycapTemplateBounds = null;
            keycapButtonBounds = null;
            keycapNameBounds = null;
            return false;
        }

        const scale = 0.58 / initialWidth;
        keycapTemplate.scale.setScalar(scale);
        keycapTemplate.position.set(-center.x * scale, -initialBounds.min.y * scale, -center.z * scale);
        keycapTemplate.updateMatrixWorld(true);
        keycapTemplateBounds = new THREE.Box3().setFromObject(keycapTemplate);
        const keycapBodyBounds = new THREE.Box3();
        keycapTemplate.traverse((child) => {
            if (child.isMesh && ! child.userData.keycapHanger) {
                keycapBodyBounds.expandByObject(child);
            }
        });
        keycapButtonBounds = new THREE.Box3();
        keycapNameBounds = new THREE.Box3();
        keycapTemplate.traverse((child) => {
            if (child.isMesh) {
                const component = getComponent(child, keycapTemplate);

                if (component === 'button') {
                    keycapButtonBounds.expandByObject(child);
                } else if (component === 'name') {
                    keycapNameBounds.expandByObject(child);
                }
            }
        });
        if (keycapButtonBounds.isEmpty()) {
            keycapButtonBounds = null;
        }
        if (keycapNameBounds.isEmpty()) {
            keycapNameBounds = null;
        }
        const normalizedSize = keycapBodyBounds.getSize(new THREE.Vector3());
        keycapTemplateUnitWidth = Math.max(normalizedSize.x, normalizedSize.z);

        return true;
    };

    const applyBambuObjectNames = (model, archiveBuffer) => {
        try {
            const archive = unzipSync(new Uint8Array(archiveBuffer));
            const modelXml = archive['3D/3dmodel.model'];
            const settingsXml = archive['Metadata/model_settings.config'];

            if (!modelXml || !settingsXml) return;

            const parser = new DOMParser();
            const modelDocument = parser.parseFromString(new TextDecoder().decode(modelXml), 'application/xml');
            const settingsDocument = parser.parseFromString(new TextDecoder().decode(settingsXml), 'application/xml');
            const objectParts = new Map();

            settingsDocument.querySelectorAll('object').forEach((object) => {
                const objectName = Array.from(object.children).find((child) => child.tagName === 'metadata' && child.getAttribute('key') === 'name')?.getAttribute('value');
                const parts = Array.from(object.children)
                    .filter((child) => child.tagName === 'part')
                    .sort((first, second) => Number(first.getAttribute('id')) - Number(second.getAttribute('id')))
                    .map((part) => ({
                        name: Array.from(part.children).find((child) => child.tagName === 'metadata' && child.getAttribute('key') === 'name')?.getAttribute('value'),
                    }));

                objectParts.set(object.getAttribute('id'), { name: objectName, parts });
            });

            const build = Array.from(modelDocument.getElementsByTagName('*')).find((element) => element.localName === 'build');
            const buildObjectIds = Array.from(build?.children ?? [])
                .filter((element) => element.localName === 'item')
                .map((item) => item.getAttribute('objectid'));

            model.children.forEach((object, index) => {
                const objectData = objectParts.get(buildObjectIds[index]);

                if (objectData?.name) object.name = objectData.name;

                objectData?.parts.forEach((part, partIndex) => {
                    if (part.name && object.children[partIndex]) {
                        object.children[partIndex].name = part.name;
                    }
                });
            });
        } catch {
            // Core 3MF object names still work when Bambu Studio metadata is unavailable.
        }
    };

    function renderKeycaps() {
        if (!keycapGenerator) return;

        disposeGeneratedModel();
        const emptyState = container.querySelector('[data-keycap-empty]');
        const characters = Array.from(customName);

        if (characters.length === 0) {
            emptyState?.classList.remove('hidden');
            emptyState?.classList.add('grid');
            return;
        }

        emptyState?.classList.add('hidden');
        emptyState?.classList.remove('grid');
        keycapModel = new THREE.Group();
        keycapModel.name = 'generated-keycaps';

        const keyWidth = Math.min(0.58, 3 / characters.length);
        const keyOverlap = keyWidth * 0.23;
        const bodyHeight = keyWidth * 0.43;
        const topHeight = keyWidth * 0.16;
        const stride = keyWidth - keyOverlap;
        const totalWidth = keyWidth * characters.length - keyOverlap * (characters.length - 1);

        characters.forEach((character, index) => {
            const keyPosition = index * stride - (totalWidth - keyWidth) / 2;

            if (keycapTemplate) {
                const key = cloneKeycapTemplate(character, index === 0);
                key.scale.multiplyScalar(keyWidth / keycapTemplateUnitWidth);
                key.position.x += keyPosition;
                keycapModel.add(key);

                return;
            }

            const key = new THREE.Group();
            key.name = `keycap-${index + 1}-${character}`;
            key.position.x = keyPosition;

            const body = new THREE.Mesh(
                new THREE.BoxGeometry(keyWidth * 0.94, bodyHeight, keyWidth * 0.94),
                new THREE.MeshStandardMaterial({ color: componentColors.base, roughness: 0.48 }),
            );
            body.geometry.userData.keycapGeneratedGeometry = true;
            body.position.y = bodyHeight / 2;
            body.castShadow = true;
            body.receiveShadow = true;
            key.add(body);

            const buttonMaterial = new THREE.MeshStandardMaterial({ color: componentColors.button, roughness: 0.42 });
            const button = new THREE.Mesh(
                new THREE.BoxGeometry(keyWidth, topHeight, keyWidth),
                buttonMaterial,
            );
            button.geometry.userData.keycapGeneratedGeometry = true;
            button.position.y = bodyHeight - topHeight * 0.36;
            button.castShadow = true;
            button.receiveShadow = true;
            key.add(button);

            const glyph = createRaisedGlyph(character, keyWidth * 0.38, componentColors.name);
            glyph.position.y = button.position.y + topHeight / 2 + 0.002;
            key.add(glyph);

            keycapModel.add(key);
        });

        scene.add(keycapModel);
        floor.position.y = -0.06;
        const floorRadius = Math.max(2.5, totalWidth * 0.9);
        floor.geometry.dispose();
        floor.geometry = new THREE.CircleGeometry(floorRadius, 64);

        const cameraDistance = Math.max(3.6, totalWidth * 1.7);
        const modelHeight = keycapTemplate ? keycapTemplateBounds.max.y : bodyHeight;
        controls.target.set(0, modelHeight * 0.58, 0);
        camera.position.set(0, cameraDistance * 0.5, cameraDistance);
        camera.lookAt(controls.target);
        controls.update();
    }

    const applyComponentColors = (colors) => {
        Object.assign(componentColors, colors);

        if (keycapGenerator) {
            renderKeycaps();
            return;
        }

        if (!activeModel) return;
        if (format !== '3mf') {
            applyColor(componentColors.base);
            return;
        }
        activeModel.traverse((child) => {
            if (!child.isMesh) return;
            const color = componentColors[getComponent(child) || 'base'];
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                material.color?.set(color);
                material.needsUpdate = true;
            });
        });
    };

    container.addEventListener('model-color-change', (event) => applyColor(event.detail));
    container.addEventListener('component-color-change', (event) => applyComponentColors(event.detail));
    container.addEventListener('keycap-name-change', (event) => {
        customName = event.detail;
        renderKeycaps();
    });

    if (keycapGenerator) {
        const templateUrl = container.dataset.keycapTemplateUrl;

        if (templateUrl) {
            fetch(templateUrl)
                .then((response) => {
                    if (!response.ok) throw new Error('3MF template request failed.');

                    return response.arrayBuffer();
                })
                .then((buffer) => {
                    const loaded = new ThreeMFLoader().parse(buffer);
                    applyBambuObjectNames(loaded, buffer);

                    if (!prepareKeycapTemplate(loaded)) {
                        container.querySelector('[data-keycap-error]')?.classList.replace('hidden', 'block');
                    }

                    renderKeycaps();
                    container.querySelector('.viewer-loading')?.remove();
                })
                .catch(() => {
                    renderKeycaps();
                    container.querySelector('.viewer-loading')?.remove();
                    container.querySelector('[data-keycap-error]')?.classList.replace('hidden', 'block');
                });
        } else {
            renderKeycaps();
            container.querySelector('.viewer-loading')?.remove();
        }
    } else {
        const loader = format === '3mf' ? new ThreeMFLoader() : new STLLoader();
        loader.load(
            container.dataset.modelUrl,
            (loaded) => {
                if (format === '3mf') {
                    activeModel = loaded;
                } else {
                    const geometry = loaded;
                    geometry.computeVertexNormals();
                    activeModel = new THREE.Mesh(
                        geometry,
                        new THREE.MeshStandardMaterial({ color: container.dataset.color || '#c9b896', roughness: 0.48, metalness: 0.02 }),
                    );
                }

                const bounds = new THREE.Box3().setFromObject(activeModel);
                const size = bounds.getSize(new THREE.Vector3());
                const center = bounds.getCenter(new THREE.Vector3());
                activeModel.position.sub(center);
                const maxDimension = Math.max(size.x, size.y, size.z) || 1;
                activeModel.scale.multiplyScalar(1.75 / maxDimension);
                activeModel.rotation.x = -Math.PI / 2;
                activeModel.traverse((child) => {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                        if (format === '3mf') {
                            const materials = Array.isArray(child.material) ? child.material : [child.material];
                            materials.forEach((material) => {
                                material.color?.set(componentColors[getComponent(child) || 'base']);
                                material.roughness = 0.5;
                            });
                        }
                    }
                });
                scene.add(activeModel);
                applyComponentColors(componentColors);
                container.querySelector('.viewer-loading')?.remove();
                controls.target.set(0, 0, 0);
                controls.update();
            },
            undefined,
            () => {
                container.querySelector('.viewer-loading')?.remove();
                const error = container.querySelector('.viewer-error');
                if (error) error.classList.replace('hidden', 'grid');
            },
        );
    }

    const animate = () => {
        controls.update();
        renderer.render(scene, camera);
        requestAnimationFrame(animate);
    };
    animate();
}

document.querySelectorAll('[data-3d-viewer]').forEach(startViewer);
