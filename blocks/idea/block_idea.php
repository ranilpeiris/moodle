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
    	$this->content->text   = 'The content of our idea block!';
    	$this->content->footer = 'Footer here...';
    
    	return $this->content;
    }
}