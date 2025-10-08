import {globSync} from 'glob';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import copy from 'rollup-plugin-copy';
import terser from "@rollup/plugin-terser";
import json from '@rollup/plugin-json';
import serve from 'rollup-plugin-serve';
import del from 'rollup-plugin-delete';
import process from 'node:process';

const build = (typeof process.env.BUILD !== 'undefined') ? process.env.BUILD : 'local';
console.log("build: " + build);
const useTerser = (process.env.ROLLUP_WATCH !== 'true' && build !== 'test');
let isRolldown = process.argv.some((arg) => arg.includes('rolldown'));

export default (async () => {
    return {
        input: (build != 'test') ? ['src/api-platform-auth.js'] : globSync('test/**/*.js'),
        output: {
            dir: '../public/auth',
            entryFileNames: '[name].js',
            chunkFileNames: 'shared/[name].[hash].js',
            format: 'esm',
            sourcemap: true
        },
        plugins: [
            del({
                targets: '../public/auth/*',
                force: true
            }),
            !isRolldown && resolve({browser: true}),
            !isRolldown && commonjs(),
            !isRolldown && json(),
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