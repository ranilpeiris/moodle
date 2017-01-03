<?php
class block_idea extends block_base {
    public function init() {
        $this->title = get_string('idea', 'block_idea');
    }
    
    public function get_content() {
    	if ($this->content !== null) {
    		return $this->content;
    	}
    
    	
    	
    	$this->content         =  new stdClass;
    	//$ideasrootid = $this->getideascategoryid();
    	$catur="/moodle/course/edit.php?category=12";
    	 
    	$menulist[] = html_writer::link( $catur, 'add new idea v1');
    	$menulist[] = '<hr />';
    	
    	// Remove the last element (will be an HR)
    	array_pop($menulist);
    	// Display the content as a list
    	$this->content->text = html_writer::alist($menulist, array('class'=>'list'));
    	 
    	$this->content->footer = 'Footer here...';
    
    	return $this->content;
    }
}