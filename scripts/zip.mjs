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
        // general
        '.git/**',
        'scripts/**',
        '**/.DS_Store',        
        
        // node
        'node_modules/**',
        'vendor/**',

        // build
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'build/**',
        
        // claude
        '.claude/**',
        'CLAUDE.md',
        'RELEASE.md',
        
        // tests     
        'tests/**',        
        '.phpunit.result.cache',        
        'phpunit.xml',
    ],
} );

output.on( 'close', () => console.log( `✓ ${filename} (${ ( archive.pointer() / 1024 ).toFixed( 1 ) } KB)` ) );
archive.on( 'error', err => { throw err; } );

archive.finalize();
