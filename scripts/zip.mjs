import archiver from 'archiver';
import { createWriteStream, mkdirSync, readFileSync } from 'fs';

const { version } = JSON.parse( readFileSync( 'package.json', 'utf8' ) );

mkdirSync( 'build', { recursive: true } );

const filename = `build/woo-subordernator-${version}.zip`;
const output   = createWriteStream( filename );
const archive  = archiver( 'zip', { zlib: { level: 9 } } );

archive.pipe( output );

archive.glob( '**/*', {
    cwd: '.',
    ignore: [
        '.git/**',
        '.claude/**',
        'node_modules/**',
        'build/**',
        'scripts/**',
        '**/.DS_Store',
        'vendor/**',
        'CLAUDE.md',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
    ],
} );

output.on( 'close', () => console.log( `✓ ${filename} (${ ( archive.pointer() / 1024 ).toFixed( 1 ) } KB)` ) );
archive.on( 'error', err => { throw err; } );

archive.finalize();
