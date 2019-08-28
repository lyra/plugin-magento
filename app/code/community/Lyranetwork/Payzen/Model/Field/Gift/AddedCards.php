<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for Magento. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Lyranetwork_Payzen_Model_Field_Gift_AddedCards extends Lyranetwork_Payzen_Model_Field_Array
{
    protected $_eventPrefix = 'payzen_field_gift_added_cards';

    /**
     * Save uploaded files before saving config value
     */
    protected function _beforeSave()
    {
        $uploadDir = Mage::getBaseDir('media') . DS . 'payzen' . DS . 'gift' . DS;

        $data = Mage::app()->getRequest()->getParam('groups');
        $cards = $data[$this->getGroupId()]['fields'][$this->getField()]['value'];

        if (! is_array($cards) || empty($cards)) {
            $this->setValue(array());
        } else {
            $i = 0;
            foreach ($cards as $key => $card) {
                $i++;

                if (empty($card)) {
                    continue;
                }

                if (empty($card['code']) || ! preg_match('#^[A-Za-z0-9\-_]+$#', $card['code'])) {
                    $this->_throwError('Card code', $i);
                }

                if (! preg_match('#^[^<>]*$#u', $card['name'])) {
                    $this->_throwError('Card name', $i);
                }

                // Load latest logo value.
                if (Mage::helper('payzen')->fileExists($uploadDir . strtolower($card['code'] . '.png'))) {
                    $cards[$key]['logo'] = strtolower($card['code'] . '.png');
                }

                // Process file upload.
                if (is_array($_FILES) && ! empty($_FILES)) {
                    $file = array();
                    $file['tmp_name'] = $_FILES['groups']['tmp_name'][$this->getGroupId()]['fields'][$this->getField()]['value'][$key]['logo'];
                    $file['name'] = $_FILES['groups']['name'][$this->getGroupId()]['fields'][$this->getField()]['value'][$key]['logo'];

                    if ($file['tmp_name'] && $file['name']) { // Is there any file uploaded for the current card.
                        if (! class_exists('Mage_Core_Model_File_Validator_Image')) {
                            Mage::helper('payzen')->log('For security reasons, please install Magento SUPEE-9767 security patch to be able to use the module image uploader.');
                            Mage::helper('payzen')->log('See more details here: https://magento.com/security/patches/supee-9767');

                            Mage::throwException(
                                Mage::helper('payzen')->__('Gift card logos cannot be uploaded. See module logs for more details.')
                            );
                        } else {
                            try {
                                $uploader = new Varien_File_Uploader($file);
                                $uploader->setAllowedExtensions(array('png'));
                                $uploader->setAllowRenameFiles(false);
                                $uploader->addValidateCallback(
                                    Mage_Core_Model_File_Validator_Image::NAME,
                                    new Mage_Core_Model_File_Validator_Image(),
                                    'validate'
                                );

                                $result = $uploader->save($uploadDir, strtolower($card['code'] . '.png'));

                                if (key_exists('file', $result) && ! empty($result['file'])) {
                                    $cards[$key]['logo'] = $result['file'];
                                }
                            } catch (Exception $e) {
                                // Upload errors.
                                $this->_throwError('Card logo', $i, $e->getMessage());
                            }
                        }
                    }
                }
            }

            $this->setValue($cards);
        }

        return parent::_beforeSave();
    }
}
