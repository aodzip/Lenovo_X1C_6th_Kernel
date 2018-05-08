#!/bin/bash
cat /sys/firmware/acpi/tables/DSDT > dsdt.aml
iasl -d dsdt.aml
patch --verbose < X1C6_S3_DSDT.patch
iasl -ve -tc dsdt.dsl
cp dsdt.hex ../Kernel/dsdt/
