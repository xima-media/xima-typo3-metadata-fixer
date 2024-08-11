<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `xima_typo3_metadata_fixer`

</div>

Small TYPO3 extension to repair missing or corrupted `sys_file_metadata`
records.

## Installation

```
composer require xima/xima-typo3-metadata-fixer
```

## Features

* Detect + create missing `sys_file_metadata`
* Detect + fix invalid image dimensions in `sys_file_metadata`
* Delete not referenced `sys_file` records including their local file.

## Usage

Run `typo3 metadata:fix` and follow the instructions of the wizard.
