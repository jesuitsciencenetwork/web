// webpack.config.js
var Encore = require('@symfony/webpack-encore');

Encore
// directory where all compiled assets will be stored
    .setOutputPath('html/build/')

    // what's the public path to this directory (relative to your project's document root dir)
    .setPublicPath('/build')

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // will output as web/build/app.js
    // .addEntry('libs', '')
    .addEntry('app', './app/Resources/js/global.js')

    // will output as web/build/global.css
    // .addStyleEntry('global', './assets/css/global.scss')
    .addStyleEntry('global', './app/Resources/less/bootstrap.less')

    // allow sass/scss files to be processed
    // .enableSassLoader()
    .enableLessLoader()

    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning()
;

// export the final configuration
module.exports = Encore.getWebpackConfig();
