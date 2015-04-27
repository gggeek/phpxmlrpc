<?php
/**
 * Makefile for phpxmlrpc library.
 * To be used with the Pake tool: https://github.com/indeyets/pake/wiki
 *
 * @todo allow user to specify location for zip command
 * @todo allow user to specify release number and tag/branch to use
 */

namespace PhpXmlRpc {

class Builder
{
    protected static $buildDir = 'build/';
    protected static $libVersion;
    protected static $sourceBranch = 'master';

    public static function libVersion()
    {
        return self::$libVersion;
    }

    public static function buildDir()
    {
        return self::$buildDir;
    }

    public static function workspaceDir()
    {
        return self::buildDir().'workspace';
    }

    /// most likely things will break if this one is moved outside of BuildDir
    public static function distDir()
    {
        return self::buildDir().'xmlrpc-'.self::libVersion();
    }

    /// these will be generated in BuildDir
    public static function distFiles()
    {
        return array(
            'xmlrpc-'.self::libVersion().'.tar.gz',
            'xmlrpc-'.self::libVersion().'.zip',
        );
    }

    public static function sourceRepo()
    {
        return 'https://github.com/gggeek/phpxmlrpc';
    }

    /// @todo move git branch to be a named option?
    public static function getOpts($args=array(), $cliOpts=array())
    {
        if (count($args) < 1)
            throw new \Exception('Missing library version argument');
        self::$libVersion = $args[0];
        if (count($args) > 1)
            self::$sourceBranch = $args[1];

        pake_echo('---'.self::$libVersion.'---');
    }
}

}

namespace {

use PhpXmlRpc\Builder;

function run_default($task=null, $args=array(), $cliOpts=array())
{
    echo "Syntax: pake {\$pake-options} \$task \$lib-version [\$git-tag]\n";
    echo "\n";
    echo "  Run 'pake help' to list all pake options\n";
    echo "  Run 'pake -T' to list all available tasks\n";
}

function run_getopts($task=null, $args=array(), $cliOpts=array())
{
    Builder::getOpts($args, $cliOpts);
}

/**
 * Downloads source code in the build workspace directory, optionally checking out the given branch/tag
 */
function run_init($task=null, $args=array(), $cliOpts=array())
{
    // download the current version into the workspace
    $targetDir = Builder::workspaceDir();
    $targetBranch = 'php53';

    // check if workspace exists and is not already set to the correct repo
    if (is_dir($targetDir) && pakeGit::isRepository($targetDir)) {
        $repo = new pakeGit($targetDir);
        $remotes = $repo->remotes();
        if (trim($remotes['origin']['fetch']) != Builder::sourceRepo()) {
            throw new Exception("Directory '$targetDir' exists and is not linked to correct git repo");
        }

        /// @todo should we not just fetch instead?
        $repo->pull();
    } else {
        pake_mkdirs(dirname($targetDir));
        $repo = pakeGit::clone_repository(Builder::sourceRepo(), Builder::workspaceDir());
    }

    $repo->checkout($targetBranch);
}

/**
 * Runs all the build steps.
 *
 * (does nothing by itself, as all the steps are managed via task dependencies)
 */
function run_build($task=null, $args=array(), $cliOpts=array())
{
}

/**
 * Generates documentation in all formats
 */
function run_doc($task=null, $args=array(), $cliOpts=array())
{
    pake_echo('TBD...');
}

function run_clean_dist()
{
    pake_remove_dir(Builder::distDir());
    $finder = pakeFinder::type('file')->name(Builder::distFiles());
    pake_remove($finder, Builder::buildDir());
}

/**
 * Creates the tarballs for a release
 */
function run_dist($task=null, $args=array(), $cliOpts=array())
{
    // copy workspace dir into dist dir, without git
    pake_mkdirs(Builder::distDir());
    $finder = pakeFinder::type('any')->ignore_version_control();
    pake_mirror($finder, realpath(Builder::workspaceDir()), realpath(Builder::distDir()));

    // remove unwanted files from dist dir

    // also: do we still need to run dos2unix?

    // create tarballs
    chdir(dirname(Builder::distDir()));
    foreach(Builder::distFiles() as $distFile) {
        // php can not really create good zip files via phar: they are not compressed!
        if (substr($distFile, -4) == '.zip') {
            $cmd = 'zip';
            $extra = '-9 -r';
            pake_sh("$cmd $distFile $extra ".basename(Builder::distDir()));
        }
        else {
            $finder = pakeFinder::type('any')->pattern(basename(Builder::distDir()).'/**');
            // see https://bugs.php.net/bug.php?id=58852
            $pharFile = str_replace(Builder::libVersion(), '_LIBVERSION_', $distFile);
            pakeArchive::createArchive($finder, '.', $pharFile);
            rename($pharFile, $distFile);
        }
    }
}

/**
 * Cleans up the build directory
 * @todo 'make clean' usually just removes the results of the build, distclean removes all but sources
 */
function run_clean($task=null, $args=array(), $cliOpts=array())
{
    pake_remove_dir(Builder::buildDir());
}

// helper task: display help text
pake_task( 'default' );
// internal task: parse cli options
pake_task('getopts');
pake_task('init', 'getopts');
pake_task('doc', 'getopts', 'init');
pake_task('build', 'getopts', 'init', 'doc');
pake_task('dist', 'getopts', 'init', 'build', 'clean-dist');
pake_task('clean-dist', 'getopts');
pake_task('clean', 'getopts');

}
