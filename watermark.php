<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class Watermark
 */
class watermark extends Module
{
    /** @var array $_postErrors */
    private $_postErrors = [];
    /** @var array $xaligns */
    private $xaligns = ['left', 'middle', 'right'];
    /** @var array $yaligns */
    private $yaligns = ['top', 'middle', 'bottom'];
    /** @var string $yAlign */
    private $yAlign;
    /** @var string $xAlign */
    private $xAlign;
    /** @var int $transparency */
    private $transparency;

    /** @var int $resize */
    private $resize;

    /** @var array $imageTypes */
    private $imageTypes = [];
    /** @var array $extensionCache */
    protected $extensionCache = [];

    /**
     * Watermark constructor.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'watermark';
        $this->tab = 'administration';
        $this->version = '2.0.5';
        $this->author = 'Cyberscorp';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Watermark', [], 'Modules.Watermark.Admin');
        $this->description = $this->trans('Protect images by watermark.', [], 'Modules.Watermark.Admin');
        $this->confirmUninstall = $this->trans(
            'Are you sure you want to delete your details?',
            [],
            'Modules.Watermark.Admin'
        );
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        $config = Configuration::getMultiple(
            [
                'WATERMARK_TYPES',
                'WATERMARK_Y_ALIGN',
                'WATERMARK_X_ALIGN',
                'WATERMARK_TRANSPARENCY',
                'WATERMARK_LOGGED',
                'WATERMARK_HASH',
                'WATERMARK_RESIZE',
            ]
        );
        if (!isset($config['WATERMARK_TYPES'])) {
            $config['WATERMARK_TYPES'] = '';
        }
        $tmp = explode(',', $config['WATERMARK_TYPES']);
        foreach (ImageType::getImagesTypes('products') as $type) {
            if (in_array($type['id_image_type'], $tmp)) {
                $this->imageTypes[] = $type;
            }
        }

        $this->yAlign = isset($config['WATERMARK_Y_ALIGN']) ? $config['WATERMARK_Y_ALIGN'] : '';
        $this->xAlign = isset($config['WATERMARK_X_ALIGN']) ? $config['WATERMARK_X_ALIGN'] : '';
        $this->transparency = isset($config['WATERMARK_TRANSPARENCY']) ? $config['WATERMARK_TRANSPARENCY'] : 60;

        if (!isset($config['WATERMARK_HASH'])) {
            Configuration::updateValue('WATERMARK_HASH', Tools::passwdGen(10));
        }

        $this->resize = isset($config['WATERMARK_RESIZE']) ? $config['WATERMARK_RESIZE'] : 50;

        if (!isset($this->transparency, $this->xAlign, $this->yAlign)) {
            $this->warning = $this->trans(
                'Watermark image must be uploaded for this module to work properly.',
                [],
                'Modules.Watermark.Admin'
            );
        }
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!extension_loaded('gd')) {
            $this->_errors[] = $this->trans(
                'You have to enable the GD extension on your server to install this module',
                [],
                'Modules.Watermark.Admin'
            );

            return false;
        }

        $this->writeHtaccessSection();
        if (!parent::install() || !$this->registerHook('actionWatermark')) {
            return false;
        }
        Configuration::updateValue('WATERMARK_TRANSPARENCY', 60);
        Configuration::updateValue('WATERMARK_RESIZE', 50);
        Configuration::updateValue('WATERMARK_Y_ALIGN', 'bottom');
        Configuration::updateValue('WATERMARK_X_ALIGN', 'right');

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!$this->removeHtaccessSection()) {
            $this->context->controller->errors[] = $this->trans(
                'Unable to remove watermark section from .htaccess file',
                [],
                'Modules.Watermark.Admin'
            );
        }

        return parent::uninstall()
            && Configuration::deleteByName('WATERMARK_TYPES')
            && Configuration::deleteByName('WATERMARK_TRANSPARENCY')
            && Configuration::deleteByName('WATERMARK_Y_ALIGN')
            && Configuration::deleteByName('WATERMARK_LOGGED')
            && Configuration::deleteByName('WATERMARK_X_ALIGN')
            && Configuration::deleteByName('WATERMARK_RESIZE');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     */
    private function _postValidation()
    {
        $yalign = Tools::getValue('yalign');
        $xalign = Tools::getValue('xalign');
        $transparency = (int)Tools::getValue('transparency');
        $resize = (int)Tools::getValue('resize');

        $types = ImageType::getImagesTypes('products');
        $idImageType = [];
        foreach ($types as $type) {
            if (Tools::getValue('WATERMARK_TYPES_' . (int)$type['id_image_type'])) {
                $idImageType['WATERMARK_TYPES_' . (int)$type['id_image_type']] = true;
            }
        }

        if (empty($transparency)) {
            $this->_postErrors[] = $this->trans('Opacity required.', [], 'Modules.Watermark.Admin');
        } elseif ($transparency < 1 || $transparency > 100) {
            $this->_postErrors[] = $this->trans('Opacity is not in allowed range.', [], 'Modules.Watermark.Admin');
        }

        if (empty($resize)) {
            $this->_postErrors[] = $this->trans('Resize required.', [], 'Modules.Watermark.Admin');
        } elseif ($resize < 1 || $resize > 100) {
            $this->_postErrors[] = $this->trans('Resize not allowed range.', [], 'Modules.Watermark.Admin');
        }

        if (empty($yalign)) {
            $this->_postErrors[] = $this->trans('Y-Align is required.', [], 'Modules.Watermark.Admin');
        } elseif (!in_array($yalign, $this->yaligns)) {
            $this->_postErrors[] = $this->trans('Y-Align is not in allowed range.', [], 'Modules.Watermark.Admin');
        }

        if (empty($xalign)) {
            $this->_postErrors[] = $this->trans('X-Align is required.', [], 'Modules.Watermark.Admin');
        } elseif (!in_array($xalign, $this->xaligns)) {
            $this->_postErrors[] = $this->trans('X-Align is not in allowed range.', [], 'Modules.Watermark.Admin');
        }
        if (!count($idImageType)) {
            $this->_postErrors[] = $this->trans('At least one image type is required.', [], 'Modules.Watermark.Admin');
        }

        if (isset($_FILES['PS_WATERMARK']['tmp_name']) && !empty($_FILES['PS_WATERMARK']['tmp_name'])) {
            if (!ImageManager::isRealImage(
                $_FILES['PS_WATERMARK']['tmp_name'],
                $_FILES['PS_WATERMARK']['type'],
                ['image/gif', 'image/jpeg', 'image/jpg', 'image/png', 'image/webp']
            )) {
                $this->_postErrors[] = $this->trans('Allowed image formats are: .gif, .jpg, .png, .webp', [], 'Admin.Global');
            }
        }

        return !count($this->_postErrors) ? true : false;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopExceptionCore
     */
    private function _postProcess()
    {
        $types = ImageType::getImagesTypes('products');
        $idImageType = [];
        foreach ($types as $type) {
            if (Tools::getValue('WATERMARK_TYPES_' . (int)$type['id_image_type'])) {
                $idImageType[] = $type['id_image_type'];
            }
        }

        Configuration::updateValue('WATERMARK_TYPES', implode(',', $idImageType));
        Configuration::updateValue('WATERMARK_Y_ALIGN', Tools::getValue('yalign'));
        Configuration::updateValue('WATERMARK_X_ALIGN', Tools::getValue('xalign'));
        Configuration::updateValue('WATERMARK_TRANSPARENCY', Tools::getValue('transparency'));
        Configuration::updateValue('WATERMARK_RESIZE', Tools::getValue('resize'));
        Configuration::updateValue('WATERMARK_LOGGED', Tools::getValue('WATERMARK_LOGGED'));

        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            $strShop = '-' . (int)$this->context->shop->id;
        } else {
            $strShop = '';
        }

        // Submited watermark
        if (isset($_FILES['PS_WATERMARK']) && !empty($_FILES['PS_WATERMARK']['tmp_name'])) {
            /* Check watermark validity */
            if ($error = ImageManager::validateUpload($_FILES['PS_WATERMARK'])) {
                $this->_errors[] = $error;
            } else {
                $type = getimagesize($_FILES['PS_WATERMARK']['tmp_name'])[2];
                $imageExt = Tools::strtolower(image_type_to_extension($type));
                if ($imageExt === '.jpeg') {
                    $imageExt = '.jpg';
                }

                $this->deleteOldWatermark($strShop);

                $destImagePath = __DIR__ . '/views/img/' . $this->name . $strShop . $imageExt;
                /* Copy new watermark */
                if (!copy(
                    $_FILES['PS_WATERMARK']['tmp_name'],
                    $destImagePath
                )) {
                    $this->_errors[] = sprintf(
                        $this->trans(
                            'An error occurred while uploading watermark: %1$s to %2$s',
                            [],
                            'Modules.Watermark.Admin'
                        ),
                        $_FILES['PS_WATERMARK']['tmp_name'],
                        $destImagePath
                    );
                }
            }
        }

        if ($this->_errors) {
            foreach ($this->_errors as $error) {
                $this->context->controller->errors[] = $error;
            }
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => $this->name, 'conf' => 6]
            ));
        }
    }

    /**
     * @return bool|string
     */
    public function getAdminDir()
    {
        $adminDir = str_replace('\\', '/', _PS_ADMIN_DIR_);
        $adminDir = explode('/', $adminDir);
        $len = count($adminDir);

        return $len > 1 ? $adminDir[$len - 1] : _PS_ADMIN_DIR_;
    }

    /**
     * @return bool
     */
    public function removeHtaccessSection()
    {
        $key1 = "\n# start ~ module watermark section";
        $key2 = "# end ~ module watermark section\n";
        $path = _PS_ROOT_DIR_ . '/.htaccess';
        if (file_exists($path) && is_writable($path)) {
            $s = Tools::file_get_contents($path);
            $p1 = strpos($s, $key1);
            $p2 = strpos($s, $key2, $p1);
            if ($p1 === false || $p2 === false) {
                return false;
            }
            $s = Tools::substr($s, 0, $p1) . Tools::substr($s, $p2 + Tools::strlen($key2));
            file_put_contents($path, $s);
        }

        return true;
    }

    /**
     * Write the watermark section to the .htaccess file
     *
     * @return boolean
     */
			public function writeHtaccessSection()
			{
					$adminDir = $this->getAdminDir();
					
					// 1. Przygotowujemy regu³y (dodane flagi [F,L] - Forbidden i Last)
					$source = "\n# start ~ module watermark section
			<IfModule mod_rewrite.c>
			Options +FollowSymLinks
			RewriteEngine On
			RewriteCond expr \"! %{HTTP_REFERER} -strmatch '*://%{HTTP_HOST}*/$adminDir/*'\"
			RewriteRule [0-9/]+/[0-9]+\\.(jpg|png|webp)$ - [F,L]
			</IfModule>
			# end ~ module watermark section\n";

					$path = _PS_ROOT_DIR_ . '/.htaccess';

					if (file_exists($path) && is_writable($path)) {
							$content = file_get_contents($path);
							
							// 2. Bezpieczeñstwo: Jeœli sekcja jakimœ cudem ju¿ tam jest, najpierw j¹ usuwamy, 
							// aby unikn¹æ duplikowania kodu w pliku .htaccess
							if (strpos($content, '# start ~ module watermark section') !== false) {
									$content = preg_replace('/# start ~ module watermark section.*# end ~ module watermark section\n*/s', '', $content);
							}

							// 3. Zapisujemy ca³y plik z doklejon¹ na koñcu sekcj¹ (BEZ FILE_APPEND)
							if (false !== file_put_contents($path, $content . $source)) {
									return true;
							}
					}

					// 4. Bezpieczna obs³uga b³êdów, która nie wy³o¿y kontrolera w tle
					if (isset($this->context->controller) && property_exists($this->context->controller, 'errors')) {
							$this->context->controller->errors[] = $this->trans(
									'Unable to add watermark section to the .htaccess file',
									[],
									'Modules.Watermark.Admin'
							);
					}

					return false;
			}


    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShopExceptionCore
     * @throws SmartyException
     */
    public function getContent()
    {
        // Modify htaccess to prevent download of original pictures
        $this->removeHtaccessSection();
        $this->writeHtaccessSection();

        $html = '';
        if (Tools::isSubmit('btnSubmit')) {
            if ($this->_postValidation()) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $html .= $this->displayError($err);
                }
            }
        }
        $html .= $this->renderForm();

        return $html;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookActionWatermark($params)
    {
        $image = new Image($params['id_image']);
        $image->id_product = $params['id_product'];
        $base = _PS_PROD_IMG_DIR_ . $image->getExistingImgPath();

        // The original is normally a .jpg, but .png / .webp originals are supported too.
        $sourceExt = $this->findOriginalExtension($base);
        $file_org = $base . '.' . $sourceExt;
        $file = $base . '-watermark.' . $sourceExt;

        $strShop = '-' . (int)$this->context->shop->id;
        if (Shop::getContext() !== Shop::CONTEXT_SHOP || !is_file(__DIR__ . '/views/img/' . $this->name . $strShop . '.' . $this->getWatermarkExtension($strShop))) {
            $strShop = '';
        }

        // First make a watermark image
        $return = $this->watermarkByImage(
            $file_org,
            __DIR__ . '/views/img/' . $this->name . $strShop . '.' . $this->getWatermarkExtension($strShop),
            $file
        );

        if (!Configuration::get('WATERMARK_HASH')) {
            Configuration::updateValue('WATERMARK_HASH', Tools::passwdGen(10));
        }

        $imageTypes = $this->imageTypes;
        if (isset($params['image_type']) && is_array($params['image_type'])) {
            // $imageTypes holds arrays, so compare by type name/id instead of array_intersect()
            // (which would cast arrays to string and raise "Array to string conversion").
            $wanted = array_map('strval', $params['image_type']);
            $imageTypes = array_values(array_filter($imageTypes, function ($type) use ($wanted) {
                return in_array((string)$type['name'], $wanted, true)
                    || in_array((string)$type['id_image_type'], $wanted, true);
            }));
        }

        $formats = $this->getGenerationFormats();

        //go through file formats defined for watermark and resize them (once per generated format: jpg, png, webp)
        foreach ($imageTypes as $imageType) {
            $name = Tools::stripslashes($imageType['name']);
            foreach ($formats as $format) {
                $newFile = $base . '-' . $name . '.' . $format;
                if (!ImageManager::resize($file, $newFile, (int)$imageType['width'], (int)$imageType['height'], $format)) {
                    $return = false;
                }

                $newFileOrg = $base . '-' . $name . '-' . Configuration::get('WATERMARK_HASH') . '.' . $format;
                if (!ImageManager::resize($file_org, $newFileOrg, (int)$imageType['width'], (int)$imageType['height'], $format)) {
                    $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * Extension of the original product image (jpg by default; jpeg / png / webp when only such a file exists).
     *
     * @param string $base Path of the image without extension
     *
     * @return string
     */
    private function findOriginalExtension($base)
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            if (is_file($base . '.' . $extension)) {
                return $extension;
            }
        }

        return 'jpg';
    }

    /**
     * Thumbnail formats the shop generates: always jpg, plus png / webp when enabled in
     * Design > Image Settings (PS_IMAGE_FORMAT, PrestaShop 8.1+). Only formats this PHP can write are returned.
     *
     * @return string[]
     */
    private function getGenerationFormats()
    {
        $formats = ['jpg'];
        $configured = (string)Configuration::get('PS_IMAGE_FORMAT');
        foreach (explode(',', $configured) as $format) {
            $format = trim(Tools::strtolower($format));
            if ($format === 'png' && function_exists('imagepng') && !in_array($format, $formats, true)) {
                $formats[] = $format;
            } elseif ($format === 'webp' && function_exists('imagewebp') && !in_array($format, $formats, true)) {
                $formats[] = $format;
            }
        }

        return $formats;
    }

    private function resizeWatermark($originalFile)
    {
        $resize = $this->resize / 100;

        $width = imagesx($originalFile);
        $height = imagesy($originalFile);
        $newWidth = max(1, (int)round($width * $resize));
        $newHeight = max(1, (int)round(($height / $width) * $newWidth));

        return [$newWidth, $newHeight];
    }

    /**
     * @param string $imagepath
     * @param string $watermarkpath
     * @param string $outputpath
     *
     * @return bool
     */
    private function watermarkByImage($imagepath, $watermarkpath, $outputpath)
    {
        $Xoffset = $Yoffset = $xpos = $ypos = 0;

        // Force image type by extension
        $watermarkExtension = Tools::strtolower(pathinfo($watermarkpath, PATHINFO_EXTENSION));
        if ($watermarkExtension === 'jpeg') {
            $watermarkExtension = 'jpg';
        }

        $imageInfo = is_file($imagepath) ? @getimagesize($imagepath) : false;
        $watermarkInfo = is_file($watermarkpath) ? @getimagesize($watermarkpath) : false;
        if (!$imageInfo || !$watermarkInfo) {
            return false;
        }
        $type = $imageInfo[2];

        $image = ImageManager::create($type, $imagepath);
        if (!$image) {
            return false;
        }

        $imagew = ImageManager::create(static::convertExtensionToImageType($watermarkExtension), $watermarkpath);
        if (!$imagew) {
            $this->context->controller->errors[] = $this->trans(
                'Watermark image format is unsupported, allowed file types are: .gif, .jpg, .png, .webp',
                [],
                'Modules.Watermark.Admin'
            );

            return false;
        }

        [$imageWidth, $imageHeight] = $imageInfo;
        [$w_width, $w_height] = $watermarkInfo;

        $watermarkSize = $this->resizeWatermark($imagew);

        $watermark = imagecreatetruecolor($watermarkSize[0], $watermarkSize[1]); // integers, see resizeWatermark()

        // integer representation of the color black (rgb: 0,0,0)
        $background = imagecolorallocate($watermark, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($watermark, $background);
        imagealphablending($watermark, false);
        imagesavealpha($watermark, true);
        imagecopyresampled($watermark, $imagew, 0, 0, 0, 0, $watermarkSize[0], $watermarkSize[1], $w_width, $w_height);

        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        if ($this->xAlign === 'middle') {
            $xpos = (int)round($imageWidth / 2 - $watermarkWidth / 2 + $Xoffset);
        }
        if ($this->xAlign === 'left') {
            $xpos = 0 + $Xoffset;
        }
        if ($this->xAlign === 'right') {
            $xpos = (int)($imageWidth - $watermarkWidth - $Xoffset);
        }
        if ($this->yAlign === 'middle') {
            $ypos = (int)round($imageHeight / 2 - $watermarkHeight / 2 + $Yoffset);
        }
        if ($this->yAlign === 'top') {
            $ypos = 0 + $Yoffset;
        }
        if ($this->yAlign === 'bottom') {
            $ypos = (int)($imageHeight - $watermarkHeight - $Yoffset);
        }
        if (!$this->imagecopymerge_alpha(
            $image,
            $watermark,
            $xpos,
            $ypos,
            0,
            0,
            $watermarkWidth,
            $watermarkHeight,
            (int)$this->transparency,
            $watermarkExtension
        )) {
            return false;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return ImageManager::write($type, $image, $outputpath);
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderForm()
    {
        $types = ImageType::getImagesTypes('products');
        foreach ($types as $key => $type) {
            $types[$key]['label'] = $type['name'] . ' (' . $type['width'] . ' x ' . $type['height'] . ')';
        }

        if (Shop::getContext() === Shop::CONTEXT_SHOP) {
            $strShop = '-' . (int)$this->context->shop->id;
        } else {
            $strShop = '';
        }

        $imageExt = $this->getWatermarkExtension($strShop);

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->trans(
                    'Once the module set up, regenerate your images with the dedicated tool in the Preferences section. Watermark will automatically be applied to new images.',
                    [],
                    'Modules.Watermark.Admin'
                ),
                'input' => [
                    [
                        'type' => 'file',
                        'label' => $this->trans('Watermark file:', [], 'Modules.Watermark.Admin'),
                        'name' => 'PS_WATERMARK',
                        'desc' => $this->trans('Allowed image formats are: .gif, .jpg, .png, .webp', [], 'Admin.Global'),
                        'thumb' => "{$this->_path}views/img/{$this->name}{$strShop}.{$imageExt}?t=" . rand(0, time()),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Watermark opacity (1-100)', [], 'Modules.Watermark.Admin'),
                        'name' => 'transparency',
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Watermark resize % (1-100)', [], 'Modules.Watermark.Admin'),
                        'name' => 'resize',
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Watermark X align:', [], 'Modules.Watermark.Admin'),
                        'name' => 'xalign',
                        'class' => 'fixed-width-md',
                        'options' => [
                            'query' => [
                                [
                                    'id' => 'left',
                                    'name' => $this->trans('Left', [], 'Admin.Global'),
                                ],
                                [
                                    'id' => 'middle',
                                    'name' => $this->trans('Middle', [], 'Admin.Global'),
                                ],
                                [
                                    'id' => 'right',
                                    'name' => $this->trans('Right', [], 'Admin.Global'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Watermark Y align:', [], 'Modules.Watermark.Admin'),
                        'name' => 'yalign',
                        'class' => 'fixed-width-md',
                        'options' => [
                            'query' => [
                                [
                                    'id' => 'top',
                                    'name' => $this->trans('Top', [], 'Admin.Global'),
                                ],
                                [
                                    'id' => 'middle',
                                    'name' => $this->trans('Middle', [], 'Admin.Global'),
                                ],
                                [
                                    'id' => 'bottom',
                                    'name' => $this->trans('Bottom', [], 'Admin.Global'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'WATERMARK_TYPES',
                        'label' => $this->trans(
                            'Choose image types for watermark protection:',
                            [],
                            'Modules.Watermark.Admin'
                        ),
                        'values' => [
                            'query' => $types,
                            'id' => 'id_image_type',
                            'name' => 'label',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'WATERMARK_LOGGED',
                        'label' => $this->trans(
                            'Logged in customers can see images without watermark',
                            [],
                            'Modules.Watermark.Admin'
                        ),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink(
            'AdminModules',
            false
        ) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public function getConfigFieldsValues()
    {
        $configFields = [
            'PS_WATERMARK' => Tools::getValue('PS_WATERMARK', Configuration::get('PS_WATERMARK')),
            'transparency' => Tools::getValue('transparency', Configuration::get('WATERMARK_TRANSPARENCY')),
            'resize' => Tools::getValue('resize', Configuration::get('WATERMARK_RESIZE')),
            'xalign' => Tools::getValue('xalign', Configuration::get('WATERMARK_X_ALIGN')),
            'yalign' => Tools::getValue('yalign', Configuration::get('WATERMARK_Y_ALIGN')),
            'WATERMARK_LOGGED' => Tools::getValue('WATERMARK_LOGGED', Configuration::get('WATERMARK_LOGGED')),
        ];
        //get all images type available
        $types = ImageType::getImagesTypes('products');
        $idImageType = [];
        foreach ($types as $type) {
            $idImageType[] = $type['id_image_type'];
        }

        //get images type from $_POST
        $idImageTypePost = [];
        foreach ($idImageType as $id) {
            if (Tools::getValue('WATERMARK_TYPES_' . (int)$id)) {
                $idImageTypePost['WATERMARK_TYPES_' . (int)$id] = true;
            }
        }

        //get images type from Configuration
        $idImageTypeConfig = [];
        if ($confs = Configuration::get('WATERMARK_TYPES')) {
            $confs = explode(',', Configuration::get('WATERMARK_TYPES'));
        } else {
            $confs = [];
        }

        foreach ($confs as $conf) {
            $idImageTypeConfig['WATERMARK_TYPES_' . (int)$conf] = true;
        }

        //return only common values and value from post
        if (Tools::isSubmit('btnSubmit')) {
            $configFields = array_merge($configFields, array_intersect($idImageTypePost, $idImageTypeConfig));
        } else {
            $configFields = array_merge($configFields, $idImageTypeConfig);
        }

        return $configFields;
    }

    /**
     * Delete old watermark images
     *
     * @param $strShop
     * @since 2.0.0
     *
     */
    private function deleteOldWatermark($strShop)
    {
        $this->extensionCache = [];
        foreach (['.jpg', '.gif', '.png', '.webp'] as $extension) {
            @unlink(__DIR__ . '/views/img/' . $this->name . $strShop . $extension);
        }
    }

    /**
     * Get the watermark icon extension, by testing its existence
     *
     * @param string $strShop Shop part of the filename
     *
     * @return string
     * @since 2.0.0
     *
     */
    public function getWatermarkExtension($strShop)
    {
        // No need to probe the filesystem every single time
        if (array_key_exists($strShop, $this->extensionCache)) {
            return $this->extensionCache[$strShop];
        }
        // Probe image file
        $imageExt = 'gif';
        foreach (['png', 'webp', 'jpg'] as $extension) {
            if (file_exists(sprintf('%s/views/img/watermark%s.%s', __DIR__, $strShop, $extension))) {
                $imageExt = $extension;
                break;
            }
        }
        $this->extensionCache[$strShop] = $imageExt;

        return $imageExt;
    }

    /**
     * Merges images and watermarks.
     *
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param $pct
     * @param $watermarkExtension
     *
     * @return bool
     * @since 2.0.0
     *
     */
    private function imagecopymerge_alpha(
        $dst_im,
        $src_im,
        $dst_x,
        $dst_y,
        $src_x,
        $src_y,
        $src_w,
        $src_h,
        $pct,
        $watermarkExtension
    ) {
        if ($watermarkExtension === 'png' || $watermarkExtension === 'webp') { // Needed for alpha transparency
            $cut = imagecreatetruecolor($src_w, $src_h);
            imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
            imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        } else {
            $cut = $src_im;
        }

        return imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
    }

    /**
     * Convert file extension to image type
     *
     * @param string $extension
     *
     * @return int
     *
     * @since 2.0.0
     */
    public static function convertExtensionToImageType($extension)
    {
        $imageTypes = [
            'jpg' => IMAGETYPE_JPEG,
            'jpeg' => IMAGETYPE_JPEG,
            'gif' => IMAGETYPE_GIF,
            'png' => IMAGETYPE_PNG,
            'webp' => defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : 18,
        ];
        if (!array_key_exists(Tools::strtolower($extension), $imageTypes)) {
            return IMAGETYPE_GIF;
        }

        return $imageTypes[Tools::strtolower($extension)];
    }
}
