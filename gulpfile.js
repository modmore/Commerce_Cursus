const gulp = require('gulp'),
    replace = require('gulp-replace'),
    pkg = require('./_build/version.json');
const year = new Date().getFullYear();

const bumpTransport = function () {
    return gulp.src([
        '_build/build.transport.php',
    ], {base: './'})
        .pipe(replace(/PKG_VERSION', '\d+\.\d+\.\d+-?\w*'/ig, 'PKG_VERSION\', \'' + pkg.version + '\''))
        .pipe(gulp.dest('.'));
};
const bumpCopyright = function () {
    return gulp.src([
        'core/components/commerce_cursus/src/Commerce_Cursus.php',
    ], {base: './'})
        .pipe(replace(/Copyright 2023(-\d{4})? by/g, 'Copyright ' + (year > 2023 ? '2023-' : '') + year + ' by'))
        .pipe(gulp.dest('.'));
};
const bumpVersion = function () {
    return gulp.src([
        'core/components/commerce_cursus/src/Commerce_Cursus.php',
    ], {base: './'})
        .pipe(replace(/version = '\d+\.\d+\.\d+-?\w*'/ig, 'version = \'' + pkg.version + '\''))
        .pipe(gulp.dest('.'));
};
const bumpComposer = function () {
    return gulp.src([
        'core/components/commerce_cursus/composer.json',
    ], {base: './'})
        .pipe(replace(/"version": "\d+\.\d+\.\d+-?[0-9a-z]*"/ig, '"version": "' + pkg.version + '"'))
        .pipe(gulp.dest('.'));
};
gulp.task('bump', gulp.series(bumpTransport, bumpCopyright, bumpVersion, bumpComposer));

// Default Task
gulp.task('default', gulp.series('bump'));

