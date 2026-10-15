# Watermark Module (Cyberscorp Fork)

[![License: AFL 3.0](https://shields.io)](https://opensource.org)
**Current Version:** 2.0.5 *(or higher)*  
**Original Author:** PrestaShop (Archived/Abandoned)  
**Maintained by:** [Cyberscorp](https://github.com)

---

## 📌 About This Fork
This repository is a **continued and active fork** of the official PrestaShop `watermark` module, which was archived and abandoned by the original creators after years of inactivity. 

Since the original module is no longer maintained, **Cyberscorp** has taken over the development to fix modern bugs, update compatibility, remove legacy code, and ensure it works flawlessly with current and upcoming PrestaShop and PHP versions.

---

## ⚙️ Requirements & Compatibility
- **PrestaShop:** 1.7.6+ (Fully tested on **8.2**, statically reviewed for **PrestaShop 9**)
- **PHP:** 7.2.5+ up to **PHP 8.5**
- **PHP Extensions:** GD (with WebP support enabled if needed)

---

## 🚀 Installation

1. Download the `.zip` archive of this repository.
2. Go to your PrestaShop Back Office -> **Modules** -> **Module Manager**.
3. Click **Upload a module** and select the downloaded ZIP file.
4. Configure the module under the Watermark settings tab.

*Alternatively, clone this repo directly into your `/modules/watermark` directory:*
bash: 
```cd your-prestashop-folder/modules/
git clone [https://github.com/watermark.git](https://github.com/watermark.git)
```

---

## 🤝 Contributing
Everyone is welcome to contribute! Since this is an independent fork, please follow these rules:
1. Create a branch for your features or bugfixes (`git checkout -b feature/amazing-feature`).
2. Submit a Pull Request directly to the `main` (or `master`) branch of **this** repository.
3. Follow the PrestaShop coding standards.

---

## 📄 License & Attribution
This project is licensed under the **Academic Free License v3.0 (AFL 3.0)**. 

* This module is based on the original `watermark` module by PrestaShop.
* Modifications, version bumps (v2.x.x), and ongoing maintenance are performed by Cyberscorp.
* Original copyright notices and license terms remain unchanged in the source code.

---

## 📜 Changelog (Cyberscorp)

Base module: PrestaShop "watermark" 2.0.0. Below are the changes introduced in subsequent versions.

### Version 2.0.3 (2026-10-15)
- **New:** You can now upload a `.png` or `.webp` file as a watermark (in addition to `.jpg` and `.gif`); WebP transparency works just like PNG.
- **New:** Original product images in JPG, JPEG, PNG, and WebP formats are now also watermarked (the module automatically detects the format of the original; the file `<id>-watermark.<format>` preserves the source format).
- **New:** Thumbnails (with and without watermark, `-<hash>`) are generated in all formats enabled in *Design > Image Settings* (always JPG, optionally PNG and WebP – only if PHP's GD can save them).
- **Change:** The `.htaccess` rule blocking direct access to originals now covers `.jpg`, `.png`, and `.webp` (refreshed automatically upon update – `upgrade/upgrade-2.0.3.php`).
- **Change:** Messages regarding allowed watermark file formats (`.gif`, `.jpg`, `.png`, `.webp`); previous translations of these sentences may need to be re-entered.
- **Fix:** Extension cache is now refreshed after uploading a new watermark.

### Version 2.0.2 (2026-10-15)
- **Fix:** `array_intersect()` on image type arrays caused an *"Array to string conversion"* warning (the `image_type` hook parameter is now matched by name or image type ID and does not change the module state).
- **Fix:** Removed `Tools::file_exists_cache()` (no guarantee of existence in PrestaShop 9) – replaced by `is_file()`.
- **Optimization:** Static review for PHP 8.5 (no usage of deprecated constructs: `imagedestroy`, casts `(integer)`/`(double)`, backticks; class properties declared – no dynamic property warnings).

### Version 2.0.1 (2026-10-14)
- **Fix:** Removed the Symfony ClassNotFoundError `CategoryFetcher` (`PrestaShop\Module\Watermark\Addons`) in module configuration caused by the missing `vendor/` directory from Composer. Removed the Addons suggestion banner (`renderAddonsSuggestion`) and related files and dependencies; `composer.json` has no dependencies, `composer.lock` removed.
- **Fix:** Adaptation for PHP 8.1+ – removed outdated implicit float -> int casting when applying watermarks (dimensions, positions, and transparency are now explicitly integers).
- **Fix:** Added safeguards against missing image / watermark files (method returns `false` instead of an error).
- **Fix:** Fixed validation for *"select at least one image type"*.
- **Security:** Added an empty, protected `vendor/` directory (`.htaccess` + `index.php`) so PrestaShop does not log messages about missing/unprotected vendor directory.

### Version 2.0.0
- Initial version (unchanged).




------------------------------------------------------
ORYGINAL README file. 

# Watermark

## About

Protect image by watermark.

## Contributing

PrestaShop modules are open-source extensions to the PrestaShop e-commerce solution. Everyone is welcome and even encouraged to contribute with their own improvements.

### Requirements

Contributors **must** follow the following rules:

* **Make your Pull Request on the "dev" branch**, NOT the "master" branch.
* Do not update the module's version number.
* Follow [the coding standards][1].

### Process in details

Contributors wishing to edit a module's files should follow the following process:

1. Create your GitHub account, if you do not have one already.
2. Fork the watermark project to your GitHub account.
3. Clone your fork to your local machine in the ```/modules``` directory of your PrestaShop installation.
4. Create a branch in your local clone of the module for your changes.
5. Change the files in your branch. Be sure to follow [the coding standards][1]!
6. Push your changed branch to your fork in your GitHub account.
7. Create a pull request for your changes **on the _'dev'_ branch** of the module's project. Be sure to follow [the commit message norm][2] in your pull request. If you need help to make a pull request, read the [Github help page about creating pull requests][3].
8. Wait for one of the core developers either to include your change in the codebase, or to comment on possible improvements you should make to your code.

That's it: you have contributed to this open-source project! Congratulations!

[1]: http://doc.prestashop.com/display/PS16/Coding+Standards
[2]: http://doc.prestashop.com/display/PS16/How+to+write+a+commit+message
[3]: https://help.github.com/articles/using-pull-requests

