<?php

# Download updates
exec('npm i');

# Copy files
copy('node_modules/bootstrap-icons/font/bootstrap-icons.json', 'font_icon/bootstrap/bootstrap-icons.json');

copy('node_modules/font-awesome/less/variables.less', 'font_icon/fa4/variables.less');
copy('node_modules/font-awesome/scss/_variables.scss', 'font_icon/fa4/variables.scss');

copy('node_modules/font-awesome-v5-icons/data/icons.json', 'font_icon/fa5/icons.json');

copy('node_modules/fontawesome-free/metadata/icons.json', 'font_icon/fa6/icons.json');

copy('node_modules/feather-icons/dist/icons.json', 'font_icon/feather/icons.json');

copy('node_modules/material-symbols/index.d.ts', 'font_icon/material/index.d.ts');
