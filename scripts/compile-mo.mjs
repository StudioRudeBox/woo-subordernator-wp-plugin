import { po, mo } from 'gettext-parser';
import { readFileSync, writeFileSync, readdirSync } from 'fs';
import { join } from 'path';

const dir = 'languages';

for ( const file of readdirSync( dir ).filter( f => f.endsWith( '.po' ) ) ) {
    const poPath = join( dir, file );
    const moPath = poPath.replace( /\.po$/, '.mo' );
    const parsed  = po.parse( readFileSync( poPath ) );
    writeFileSync( moPath, mo.compile( parsed ) );
    console.log( `✓ ${ moPath }` );
}
