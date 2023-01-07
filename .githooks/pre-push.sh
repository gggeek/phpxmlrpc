#!/bin/bash

# Script to be run as part of the github pre-push hook.
#
# Checks that, if there is a "version-like" tag being pushed, all the files which are supposed to contain the tag do
# actually have the correct tag value in them. If they do not, the push is blocked.
# NB: this does _not_ automatically alter the source files and commit them with the correct tag value, nor prevent the
# tag to be added to the wrong git commit locally (ie. a commit in which the source files have the wrong tag value).
# All it does is prevent the developer from pushing the 'bad tags' to remote repositories, giving him/her the chance to
# manually rectify the situation on the local repo before retrying to push.
#
# @todo could this be run as pre-commit hook instead? We have to test if adding a tag does trigger pre-commit hook...
# @see https://stackoverflow.com/questions/56538621/git-hook-to-check-tag-name
# @see https://stackoverflow.com/questions/8418071/is-there-a-way-to-check-that-a-git-tag-matches-the-content-of-the-corresponding
#      for an alternative take (enforcing this with a server-side hook)
#
# NB: remember that this can be run within a windows env too, via fe. the tortoisegit or the git-4-win on the cli!
# git for windows comes with its own copy of common unix utils such as bash, grep. But they are sometimes old and/or
# buggy compared to what one gets in current linux distros :-(
#
# This hook is called with the following parameters:
#
# $1 -- Name of the remote to which the push is being done
# $2 -- URL to which the push is being done
#
# If pushing without using a named remote those arguments will be equal.
#
# Information about the commits which are being pushed is supplied as lines to
# the standard input in the form:
#
#   <local ref> <local oid> <remote ref> <remote oid>

# We do not abort the push in case there is an error in this script. No `set -e`
#set -e

# @todo detect if this is run outside git hook, and give a warning plus explain how to pass in $local_ref $local_oid $remote_ref $remote_oid
# @todo allow a git config parameter to switch on/off a 'verbose mode'
# @todo we could allow the variables `files` and `version_tag_regexp` to be set via git config parameters instead of hardcoded

# List of files which do contain the version tag
files='NEWS.md src/PhpXmlRpc.php doc/manual/phpxmlrpc_manual.adoc'

# Regexp use to decide if a git tag is a version label
version_tag_regexp='^v?[0-9]{1,4}\.[0-9]{1,4}(\.[0-9]{1,4})?'

# Create a string of '0' chars of appropriate length for the current git version
zero="$(git hash-object --stdin </dev/null | tr '[0-9a-f]' '0')"

echo "Checking commits for version tags before push..."

# check all commits which we are pushing
while read local_ref local_oid remote_ref remote_oid; do
    #echo "Checking commit $local_oid ..."
    # skip ref deletions
    if [ "$local_oid" != "$zero" ]; then
        #if [ "$remote_oid" = "$zero" ]; then
        #    # 'new branch'
        #    range="$local_oid"
        #else
        #    # 'update to existing branch'
        #    range="$remote_oid..$local_oid"
        #fi
        # @todo in case we have a range (see commented out code 2 lines above), should we check more commits?
        tags="$(git tag --points-at $local_oid)"
        if [ -n "$tags" ]; then
            # @todo this will not work predictably if there are 2 version tags attached to the same commit. Which probably
            #       there should not be anyway. Should we check for that too and abort in case?
            while IFS= read -r tag; do
                echo "Found tag: '$tag'..."
                if [[ "$tag" =~ $version_tag_regexp ]]; then
                    echo "Tag looks like a version number. Checking if code is matching..."
                    for file in $files; do
                        if [ ! -f "$file" ]; then
                            echo "File is missing: '$file'. Please fix config of github hook script"
                            exit 2
                        fi
                        echo "Looking for '$tag' in '$file'"
                        # @todo atm if the version tag is f.e. v1.1, any file containing the string "clamav1.10' will
                        #       match. We should improve this match to avoid such scenarios
                        # Note: we can not use `-i` as it crashes git-4-win's grep
                        if grep -F -q "$tag" "$file"; then
                            :
                        else
                            echo "Tag is missing from file '$file'"
                            exit 1
                        fi
                    done
                    echo "All files ok!"
                    break 2; # exit from both while loops: no need to check for further tags
                fi
            done <<< "$tags"
        fi
    fi
done

exit 0
