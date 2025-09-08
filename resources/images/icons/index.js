import fs from 'fs';
import path from 'path';
import os from 'os';

const icons = fs.readdirSync('./');

let new_icons = [];

const iconComponentPath = path.join('/-/reforged-spork/resources/js/', 'Components/Icons');
let imports = [];
console.log('<template>\n    <div class="flex flex-wrap gap-4">');
for (let i = 0; i < icons.length; i++) {
    const icon = icons[i];
    if (icon.endsWith('.svg')) {

        const iconName = icon.split('-').filter(part => ![
            'Streamline',
            'Pixel.svg',
            '',
        ].includes(part)).concat(['Icon']).join('')
        imports.push(`    import ${iconName} from '@/Components/Icons/${iconName}';`);
        console.log('    <'+path.basename(iconName, '.svg'), 'class="w-8 h-8 fill-current" />');

        fs.writeFileSync(
            path.join(iconComponentPath+ '.vue'),
            `<template>\n` + fs.readFileSync(icon) + `\n</template>\n\n<script setup>\n\n</script>`
        );
    }
}
console.log('    </div>\n</template>\n');
console.log('<script setup>');
console.log(imports.join('\n'));
console.log('\n</script>\n');


// console.log(icons)
