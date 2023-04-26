import glob from 'glob';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import copy from 'rollup-plugin-copy';
import terser from "@rollup/plugin-terser";
import json from '@rollup/plugin-json';
import serve from 'rollup-plugin-serve';
import del from 'rollup-plugin-delete';

const build = (typeof process.env.BUILD !== 'undefined') ? process.env.BUILD : 'local';
console.log("build: " + build);
const useTerser = (process.env.ROLLUP_WATCH !== 'true' && build !== 'test')

export default (async () => {
    return {
        input: (build != 'test') ? ['src/api-platform-auth.js'] : glob.sync('test/**/*.js'),
        output: {
            dir: '../public/auth',
            entryFileNames: '[name].js',
            chunkFileNames: 'shared/[name].[hash].[format].js',
            format: 'esm',
            sourcemap: true
        },
        onwarn: function (warning, warn) {
            // ignore chai warnings
            if (warning.code === 'CIRCULAR_DEPENDENCY') {
                return;
            }
            warn(warning);
        },
        plugins: [
            del({
                targets: '../public/auth/*',
                force: true
            }),
            resolve(),
            commonjs(),
            json(),
            useTerser ? terser() : false,
            copy({
                targets: [
                    {src: 'assets/index.html', dest: '../public/auth'},
                    {src: 'assets/silent-check-sso.html', dest: '../public/auth'},
                ],
            }),
            (process.env.ROLLUP_WATCH === 'true') ? serve({contentBase: '../public/auth', host: '127.0.0.1', port: 8002}) : false
        ]
    };
})();