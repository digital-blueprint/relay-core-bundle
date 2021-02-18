import resolve from '@rollup/plugin-node-resolve';

export async function getDistPath(packageName, assetPath) {
    if (assetPath === undefined)
        assetPath = '';
    // make sure the package exists to avoid typos
    await getPackagePath(packageName, '');
    return path.join('local', packageName, assetPath);
}

export async function getPackagePath(packageName, assetPath) {
    const r = resolve();
    const resolved = await r.resolveId(packageName);
    let packageRoot;
    if (resolved !== null) {
        const id = (await r.resolveId(packageName)).id;
        const packageInfo = r.getPackageInfoForId(id);
        packageRoot = packageInfo.root;
    } else {
        // Non JS packages
        packageRoot = path.dirname(require.resolve(packageName + '/package.json'));
    }
    return path.relative(process.cwd(), path.join(packageRoot, assetPath));
}
