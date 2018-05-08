<?php
exec("rm -rf build debs");
mkdir('build');
mkdir('build/patches');
mkdir('build/kernel');
mkdir('debs');
$version = $argv[1];
$URL = 'http://kernel.ubuntu.com/~kernel-ppa/mainline/' . $version . '/';
echo "Fetching patch list..." . PHP_EOL;
$SOURCES = file_get_contents($URL . 'SOURCES');
$patches = explode("\n", $SOURCES);
unset($patches[0]);
unset($patches[count($patches)]);
$patches = array_values($patches);
print_r($patches);
foreach ($patches as $patch) {
    echo "Downloading Patch: {$patch}..." . PHP_EOL;
    file_put_contents("build/patches/{$patch}", file_get_contents($URL . $patch));
}
echo "Fetching Local Patches..." . PHP_EOL;
$local_patches = scandir('patches');
unset($local_patches[0]);
unset($local_patches[1]);
$local_patches = array_values($local_patches);
foreach ($local_patches as $patch) {
    copy("patches/{$patch}", "build/patches/{$patch}");
}
print_r($local_patches);
$patches = array_merge($patches, $local_patches);
echo "Patch List:" . PHP_EOL;
print_r($patches);
echo "Cloning Kernel Sources..." . PHP_EOL;
passthru("git clone --branch {$version} --depth 1 git://git.launchpad.net/~ubuntu-kernel-test/ubuntu/+source/linux/+git/mainline-crack build/kernel");
foreach ($patches as $patch) {
    echo "Applying Patch: {$patch}..." . PHP_EOL;
    passthru("patch -d build/kernel -p1 < build/patches/{$patch}");
}
echo "Building Kernel..." . PHP_EOL;
passthru("cd build/kernel && chmod a+x debian/rules");
passthru("cd build/kernel && debian/scripts/*");
passthru("cd build/kernel && chmod a+x debian/scripts/misc/*");
passthru("cd build/kernel && fakeroot debian/rules clean");
echo "Enable ACPI Custom DSDT..." . PHP_EOL;
file_put_contents("build/kernel/debian.master/config/config.common.ubuntu", PHP_EOL, FILE_APPEND);
file_put_contents("build/kernel/debian.master/config/config.common.ubuntu", "CONFIG_STANDALONE=n" . PHP_EOL, FILE_APPEND);
file_put_contents("build/kernel/debian.master/config/config.common.ubuntu", "CONFIG_ACPI_CUSTOM_DSDT=y" . PHP_EOL, FILE_APPEND);
file_put_contents("build/kernel/debian.master/config/config.common.ubuntu", 'CONFIG_ACPI_CUSTOM_DSDT_FILE="' . __DIR__ . '/dsdt/dsdt.hex"' . PHP_EOL, FILE_APPEND);
echo "Building Kernel..." . PHP_EOL;
passthru("cd build/kernel && fakeroot debian/rules binary-headers binary-generic");
echo "Copy Target DEBs..." . PHP_EOL;
exec("cp build/linux-headers*all.deb debs");
exec("cp build/linux-image-unsigned*amd64.deb debs");
exec("cp build/linux-image-unsigned*amd64.deb debs");
exec("cp build/linux-modules*amd64.deb debs");
echo "Done!" . PHP_EOL;
