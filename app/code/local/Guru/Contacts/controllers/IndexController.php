<?php 
require_once "Mage/Contacts/controllers/IndexController.php";

class Guru_Contacts_IndexController extends Mage_Contacts_IndexController
{
	private $_emailer; 
	
	
    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('contactForm')
            ->setFormAction( Mage::getUrl('*/*/post') );

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }
	
	
	
    public function postAction()
    {
		$this->_emailer = Mage::getModel('core/email_template');

        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);
 
                $error = false;
 
                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }
 
                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }
 
                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }
 
                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }
 
				//var_dump($_FILES['attachment']['name']); die;
                /**************************************************************/
				if (is_array($_FILES['attachment']['name'])) {
					for($j=0; $j<count($_FILES['attachment']['name']); $j++) {
						$this->_upload($j);
						
						
						
					}
				}
				
				
                /**************************************************************/
 
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
 
                /**************************************************************/
                //sending file as attachment
                /**************************************************************/
 
                $this->_emailer->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );
 
                if (!$this->_emailer->getSentSuccess()) {
                    throw new Exception();
                }
 
                $translate->setTranslateInline(true);
 
                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                $this->_redirect('*/*/');
 
                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);
 
                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('*/*/');
                return;
            }
 
        } else {
            $this->_redirect('*/*/');
        }
    }
	
	
	
	
    private function _upload($j)
	{ 
		try {
			$fileName       =  $_FILES['attachment']['name'][$j];
			$fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
			$fileNamewoe    = rtrim($fileName, $fileExt);
			$fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
			// print_r($_FILES);die;
			$uploader       = new Varien_File_Uploader(array(
    'name' => $_FILES['attachment']['name'][$j],
    'type' => $_FILES['attachment']['type'][$j],
    'tmp_name' => $_FILES['attachment']['tmp_name'][$j],
    'error' => $_FILES['attachment']['error'][$j],
    'size' => $_FILES['attachment']['size'][$j]
        ));
			$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
			$uploader->setAllowRenameFiles(true);
			$uploader->setFilesDispersion(false);
			$path = Mage::getBaseDir('media') . DS . 'contacts';
			if(!is_dir($path)){
				mkdir($path, 0777, true);
			}
			$uploader->save($path . DS, $fileName );

		} catch (Exception $e) {
			
			Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Please upload right file type. Right  file types are png, jpeg,jpg'));
		
			$this->_redirect('*/*/');
			return;
		}
		try {
			
			$attachmentFilePath = Mage::getBaseDir('media'). DS . 'contacts' . DS . $fileName;
			
			if(file_exists($attachmentFilePath)){
				$fileContents = file_get_contents($attachmentFilePath);
				$attachment   = $this->_emailer->getMail()->createAttachment($fileContents);
				$attachment->filename[] = $fileName;
			}
		} catch ( Exception $e) {
			
			Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('File could not be attached'));
		
			$this->_redirect('*/*/');
			return;
			
		}
	} 
	
	
	
	
	
}




 