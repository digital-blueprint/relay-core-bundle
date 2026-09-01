import {globSync} from 'node:fs';
import copy from 'rollup-plugin-copy';
import serve from 'rollup-plugin-serve';
import process from 'node:process';

const build = (typeof process.env.BUILD !== 'undefined') ? process.env.BUILD : 'local';
console.log("build: " + build);
const prodBuild = (process.env.ROLLUP_WATCH !== 'true' && build !== 'test');

export default (async () => {
    return {
        input: (build != 'test') ? ['src/api-platform-auth.js'] : globSync('test/**/*.js'),
        output: {
            dir: (build != 'test') ? '../public/auth' : 'dist',
            entryFileNames: '[name].js',
            chunkFileNames: 'shared/[name].[hash].js',
            format: 'esm',
            sourcemap: true,
            minify: prodBuild,
            cleanDir: true,
        },
        plugins: [
            copy({
                hook: 'generateBundle',
                targets: [
                    {src: 'assets/index.html', dest: '../public/auth'},
                    {src: 'assets/silent-check-sso.html', dest: '../public/auth'},
                ],
            }),
            (process.env.ROLLUP_WATCH === 'true') ? serve({contentBase: '../public/auth', host: '127.0.0.1', port: 8002}) : false
        ]
    };
})();