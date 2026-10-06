import * as THREE from 'three';
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
    const componentColors = {
        base: container.dataset.baseColor || container.dataset.color || '#c9b896',
        button: container.dataset.buttonColor || container.dataset.color || '#c9b896',
        name: container.dataset.nameColor || container.dataset.color || '#c9b896',
    };
    const getComponent = (object) => {
        let current = object;
        while (current && current !== activeModel?.parent) {
            const name = current.name.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (name.includes('button') || name.includes('tombol')) return 'button';
            if (name.includes('name') || name.includes('text') || name.includes('tulisan')) return 'name';
            if (name.includes('base') || name.includes('body')) return 'base';
            current = current.parent;
        }
        return 'base';
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

    const applyComponentColors = (colors) => {
        Object.assign(componentColors, colors);
        if (!activeModel) return;
        if (format !== '3mf') {
            applyColor(componentColors.base);
            return;
        }
        activeModel.traverse((child) => {
            if (!child.isMesh) return;
            const color = componentColors[getComponent(child)];
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                material.color?.set(color);
                material.needsUpdate = true;
            });
        });
    };

    container.addEventListener('model-color-change', (event) => applyColor(event.detail));
    container.addEventListener('component-color-change', (event) => applyComponentColors(event.detail));
    const format = container.dataset.modelFormat?.toLowerCase();
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
                            material.color?.set(componentColors[getComponent(child)]);
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

    const animate = () => {
        controls.update();
        renderer.render(scene, camera);
        requestAnimationFrame(animate);
    };
    animate();
}

document.querySelectorAll('[data-3d-viewer]').forEach(startViewer);
