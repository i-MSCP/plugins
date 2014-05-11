## DEVELOPERS GUIDELINE

### Upstream sources modifications

Any change made to the upstream sources *must* be provided in the imscp.patch patch, which is automatically applied on
upstream sources during plugin installation. This mean that you *must* never do any change on the upstream sources
directly.

Any change must be documented in the CHANGES file.

### Upstream bugs

All upstream bugs, which are fixed by the imscp.patch patch *should* be reported to the upstream author:

 - https://savannah.nongnu.org/bugs/?group=jailkit

**Note:** Only the bugs should be reported. This doesn't not include any i-MSCP specific change.

### Upstream sources update

If you must update the upstream sources to a new version, you *must* ensure that the imscp.patch patch still apply
correctly. In case a fix provided by the imscp.patch patch has been integrated in the upstream sources, it must be
removed from the patch.

### Patch creation

1. Create your working tree
 $ cp -r jailkit jailkit_wrk
2. Apply the imscp.patch patch on your working tree
 $ cd jailkit_wrk && patch -p1 < ../imscp.patch
3. Do your changes in your working tree and update the patch
 $ cd .. && diff -rupN jailkit jailkit_wrk > imscp.patch
4. Test your changes by installing the plugin...
5. Delete your working tree
 $ rm -r jailkit_wrk
6. Update the CHANGES file
7. Push your changes
