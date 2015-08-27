<?php

/**
 * RandomSpaceSidebarWidget renders the RandomSpacePanel
 * to the Dashboard.
 *
 * @package humhub-modules-randomspace
 * @author Jordan Thompson, Rajesh Purohit
 */
class RandomSpaceSidebarWidget extends HWidget {
	
	/**
     * Limit Check for Minimum Spaces available.
     * 
     * @var int
     */
    public $totalSpacelimit = 1;
    
    /**
     * Render random space information
     *
     * @render random space widget
     */
    public function run() {
    	
        $css = Yii::app()->assetManager->publish(dirname(__FILE__) . '/../css', true, 0, defined('YII_DEBUG'));
        Yii::app()->clientScript->registerCssFile($css . '/randomspace.css');
		
		//If user is guest then look for spaces with visibility set for all.
		if (Yii::app()->user->isGuest) {
			$sql = "SELECT * FROM space WHERE visibility =" . Space::VISIBILITY_ALL . " AND status =" . Space::STATUS_ENABLED;
		} else {
			$sql = "SELECT * FROM space WHERE visibility IN (" . Space::VISIBILITY_ALL . "," . Space::VISIBILITY_REGISTERED_ONLY . ") AND status =" . Space::STATUS_ENABLED;
		}
		
		//Count public spaces.
		$spaces_record = Space::model()->findAllBySql($sql);
		
		if (count($spaces_record) < (int)$this->totalSpacelimit) {
			return;
		}
		
		$maxCount = count($spaces_record);
		if ($maxCount !==null && $maxCount !==0) {
			 $randNum = rand(0,($maxCount-1));
			 $spaceInfo = $this->getRandomSpace($randNum);
		     if ( is_object($spaceInfo[0]) && is_array($spaceInfo[1]) ) {
		         $this->render ( 'RandomSpacePanel', array (
		             'css' => $css, 
		             'space' => $spaceInfo[0],
		             'members' => $spaceInfo[1]
		         ) );
		     } 
		}
    }
    
    
    /**
     * Get a random space model from DB
     *
     * @return array of space and membership information from Space Model.
     */
    private function getRandomSpace($randNum) {
    
        $criteria = new CDbCriteria;
        $criteria->limit = 1;
		$criteria->offset = $randNum;
		
		if (Yii::app()->user->isGuest) {
			$criteria->condition = 'visibility =' . Space::VISIBILITY_ALL . " AND status =" . Space::STATUS_ENABLED;
		} else {
			$criteria->condition = "visibility IN (" . Space::VISIBILITY_ALL . ",". Space::VISIBILITY_REGISTERED_ONLY . ") AND status =" . Space::STATUS_ENABLED;
		}
		
        $spaces = Space::model()->findAll($criteria);
		
        if ( !empty($spaces)) {
        	foreach ($spaces as $space) {
				$members = array();
	            $membership = Yii::app()->db->createCommand()
	                    ->select('user_id')
	                	->from('space_membership')
	                	->where('space_id=:id', array(':id'=>$space->id))
	                	->queryAll();
				if ($membership !== null) {
					foreach ( $membership as $member ) {
	                $members[] = User::model()->findByPk($member['user_id']);
	            	}
	            	return array($space, $members);
				}	
			}
            
        } 
    }
    
    
}
