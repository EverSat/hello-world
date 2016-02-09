<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.folder');

if (!class_exists('plgUserJoobitileuser')) {
	class plgUserJoobitileuser extends JPlugin
	{
		const AVATAR_DEFAULT_M = 'images/avatar/avatar_m.png';
		const AVATAR_DEFAULT_F = 'images/avatar/avatar_f.png';

	    public function __construct(& $subject, $config) {
	        parent::__construct($subject, $config);
	        include_once JPATH_ROOT . '/components/com_community/libraries/core.php';
	        require_once JPATH_ROOT . '/components/com_community/events/router.php';
	        require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'lib'.DS.'class.upload.php';
	    }

		public function onUserAfterSave($user, $isnew=1, $success='', $msg='') {
			// $this->createJoobiTile(array(
			//     'link' => JRoute::_('index.php?option=com_community&view=profile&userid=' . $user['id'] ),
			//     'user' => $user,
			//     'new' => $isnew,
			// ));			
			// return true;
			//Jtile::log(get_class($this), 'privet');

			// //  ini_set('display_errors',1);
			// //  ini_set('display_startup_errors',1);
			// //  error_reporting(E_ALL ^ E_NOTICE);

			// // echo "onAfterStoreUser<pre>";
			// // print_r($user);
			// // print_r($isnew);
			// // print_r($success);
			// // print_r($msg);

			// $db = JFactory::getDBO();
   //          $query = $db->getQuery(true);
   //          $query
   //          	->select('avatar')
   //          	->from($db->quoteName('#__community_users'))
   //                  ->where('userid = '.(int)$user['id']);

   //          $db->setQuery($query);
   //          $db->setQuery($query);
   //          $obj = $db->loadObject();
   //          if ($db->getErrorNum()) {
   //              JError::raiseError(500, $db->stderr());
   //          }


			//  $this->createJoobiTile(array(
			//     'link' => JRoute::_('index.php?option=com_community&view=profile&userid=' . $user['id'] ),
			//     'user' => $user,
			//     'new' => $isnew,
			//     'imageId' => $obj->avatar,
			//  ));		
		}

		function onUserAfterDelete($user, $success, $msg) {
			if (!$success)
			{
				return false;
			}

			Jtile::joobitilePlugin('com_k2_item', 'onAfterDelete', array(
				'type' => 'user',
				'id' => $user['id'],
			));

			return true;
		}

    	function onUserLogin($user, $options = array()) {
			$isnew = 1;

			$this->createJoobiTile(array(
			    'link' => JRoute::_('index.php?option=com_community&view=profile&userid=' . $user['id'] ),
			    'user' => $user,
			    'new' => $isnew,
			));

	        return true;
	    }

	    private function handleAvatar($user_id) {
	    	
	    	$file = JRequest::get('files');
			$savepath = JPATH_ROOT.DS.'images'.DS.'avatar'.DS;

			if (isset($file['image']) && $file['image']['error'] == 0 && !JRequest::getBool('del_image'))
			{
				$handle = new Upload($file['image']);
				$handle->allowed = array('image/*');
				if ($handle->uploaded)
				{
					$handle->file_auto_rename = false;
					$handle->file_overwrite = true;
					$handle->file_new_name_body = $user_id;
					$handle->image_resize = true;
					$handle->image_ratio_y = true;
					$handle->image_x = $params->get('userImageWidth', '100');
					$handle->Process($savepath);
					$handle->Clean();
				}
				else
				{
					$mainframe->enqueueMessage(JText::_('JOOBITILE_COULD_NOT_UPLOAD_YOUR_IMAGE').$handle->error, 'notice');
				}
				$avatar = $handle->file_dst_name;
			}

			if (JRequest::getBool('del_image'))
			{

				if (JFile::exists($savepath.$avatar))
				{
					JFile::delete($savepath.$avatar);
				}
				$avatar = self::AVATAR_DEFAULT_M;
			}

			return $avatar;		
	    }		

		public function createJoobiTile($params = array()) {
			$user = JFactory::getUser($params['user']['id']);			

			$db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query
            	->select('avatar, status')
            	->from($db->quoteName('#__community_users'))
                    ->where('userid = '.(int)$user->id);

            $db->setQuery($query);
            $objCommunity = $db->loadObject();
            if ($db->getErrorNum()) {
                JError::raiseError(500, $db->stderr());
            }

            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query
            	->select('gender, description')
            	->from($db->quoteName('#__k2_users'))
                    ->where('userID = '.(int)$user->id);
            $db->setQuery($query);
            $objk2 = $db->loadObject();
            if ($db->getErrorNum()) {
                JError::raiseError(500, $db->stderr());
            }

            $imageId = $objCommunity->avatar ?: ($objk2->gender == 'f' ? self::AVATAR_DEFAULT_F : self::AVATAR_DEFAULT_M);
            $description = $obj->status ?: $objk2->description;

			$item = array(
				'type' => JTile::ESSENSE_USER,
				'new' => $params['new'],
				'id' => $user->id,
				'name' => $user->name,
				'description' => $description,
				'imageId' => $imageId,
				'publish' => !$user->block,
			);

			$params = $params + array(
			 	'content' => $description ?: 'Hello world!',
			 	'image' => '<img src="'.$item['imageId'].'" alt=""/>',
			);

			//$this->postJoobiTile($item, $params);

			Jtile::joobitilePlugin('com_k2_item', ($item['new'] ? 'onAfterCreate' : 'onAfterUpdate'), $item, $params);	
		}

		// public function postJoobiTile($item, $params = array()) {
		// 	JPluginHelper::importPlugin('joobitile');
		// 	$dispatcher	=JDispatcher::getInstance();
		// 	$dispatcher->trigger(($item['new'] ? 'onAfterCreate' : 'onAfterUpdate'), array( 'com_k2_item', $item, $params));
		// }		
	}
}