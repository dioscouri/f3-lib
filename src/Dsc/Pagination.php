<?php 
namespace Dsc;

/**
 Pagination class for the PHP Fat-Free Framework

 The contents of this file are subject to the terms of the GNU General
 Public License Version 3.0. You may not use this file except in
 compliance with the license. Any of the license terms and conditions
 can be waived if you get permission from the copyright holder.

 Copyright (c) 2012 by ikkez
 Christian Knuth <mail@ikkez.de>

 @version 1.4.1
 **/

class Pagination
{
    private $items_count;
    private $items_per_page;
    private $range = 3;
    private $current_page;
    private $template = 'common/pagination.php';
    private $routeKey;
    private $routeKeyPrefix = "page/";
    private $linkPath;
    private $fw;

    const
    TEXT_MissingItemsAttr='You need to specify items attribute for a pagination.';
    
    /*
    * EXAMPLE:: 
    * To make this work, Your routes need to map to your display functions when the page has the suffix,
    * and a number in the route. 
    * $f3->route("GET|POST /{$base}/@resource/page/@page", "{$namespace}@resource->display");
    * $f3->route("GET|POST /{$base}/@resource/@action/page/@page", "{$namespace}@resource->@action");    
    *  
    * if you want you can over ride the routeKeyPrefix in your config files, 
    * PAGINATION_KEY=split/ PAGINATION_KEY=somethingelse/
    *
    */



    /**
     * create new pagination
     * @param $items array|integer max items or array to count
     * @param $limit int max items per page
     * @param $routeKey string the key for pagination in your routing
     */
    public function __construct( $items, $limit = 10, $routeKey = 'page' ) {
        $this->fw = \Base::instance();
        $this->items_count = is_array($items)?count($items):$items;
        $this->routeKey = $routeKey;
        $this->setLimit($limit);

        if($key = $this->fw->get('PAGINATION_KEY')) {
            $this->setRouteKeyPrefix($key);
        }

    }

    /**
     * set maximum items shown on one page
     * @param $limit int
     */
    public function setLimit($limit) {
        if(is_numeric($limit))
            $this->items_per_page = $limit;
        $this->setCurrent( self::findCurrentPage($this->routeKey));
    }

    /**
     * set token name used in your route pattern for pagination
     * @param string $key
     */
    public function setRouteKey($key) {
        $this->routeKey = $key;
    }

    /**
     * set a prefix that is added to your page links
     * @param string $prefix
     */
    public function setRouteKeyPrefix($prefix) {
        $this->routeKeyPrefix = $prefix;
    }

    /**
     * set path for the template file
     * @param $template string
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * set the range of pages, that are displayed prev and next to current page
     * @param $range int
     */
    public function setRange($range) {
        if(is_numeric($range)) $this->range = $range;
    }

    /**
     * set the current page number
     * @param $current int
     */
    public function setCurrent($current) {
        if(!$this->routeKeyPrefix)
            $current = str_replace($this->routeKeyPrefix,'',$current);
        if(!is_numeric($current)) return;
        if($current <= $this->getMax()) $this->current_page = $current;
        else $this->current_page = $this->getMax();
    }

    /**
     * set path to current routing for link building
     * @param $linkPath
     */
    public function setLinkPath($linkPath) {
        $this->linkPath = (substr($linkPath,0,1) != '/') ? '/'.$linkPath:$linkPath;
        if(substr($this->linkPath,-1) != '/') $this->linkPath .= '/';
    }

    /**
     * extract the current page number from the route parameter token
     * @param string $key
     * @return int|mixed
     */
    static public function findCurrentPage($key='page') {
        $f3 = \Base::instance();
        return $f3->exists('PARAMS.'.$key) ?
        preg_replace("/[^0-9]/", "", $f3->get('PARAMS.'.$key)) : 1;
    }

    /**
     * returns the current page number
     * @return int
     */
    public function getCurrent() {
        return $this->current_page;
    }

    /**
     * returns the maximum count of items to display in pages
     * @return int
     */
    public function getItemCount() {
        return $this->items_count;
    }

    /**
     * get maximum pages needed to display all items
     * @return int
     */
    public function getMax() {
        return ceil($this->items_count / $this->items_per_page);
    }


    /**
     * get next page number
     * @return int|bool
     */
    public function getNext() {
        $nextPage = $this->current_page + 1;
        if( $nextPage > $this->getMax() ) return false;
        return $nextPage;
    }

    /**
     * get previous page number
     * @return int|bool
     */
    public function getPrev() {
        $prevPage = $this->current_page - 1;
        if( $prevPage < 1 ) return false;
        return $prevPage;
    }

    /**
     * return last page number, if current page is not in range
     * @return bool|int
     */
    public function getLast() {
        return ($this->current_page < $this->getMax() - $this->range ) ? $this->getMax() : false;
    }

    /**
     * return first page number, if current page is not in range
     * @return bool|int
     */
    public function getFirst() {
        return ($this->current_page > 3) ? 1 : false;
    }

    /**
     * return all page numbers within the given range
     * @param $range int
     * @return array page numbers in range
     */
    public function getInRange($range = null) {
        if(is_null($range))
            $range = $this->range;
        $current_range = array( ($this->current_page-$range < 1 ? 1 : $this->current_page-$range),
                ($this->current_page+$range > $this->getMax() ? $this->getMax() : $this->current_page+$range));
        $rangeIDs = array();
        for($x = $current_range[0]; $x <= $current_range[1]; ++$x) {
            $rangeIDs[] = $x;
        }
        return $rangeIDs;
    }

    /**
     * returns the number of items left behind for current page
     * @return int
     */
    public function getItemOffset() {
        return ($this->current_page - 1) * $this->items_per_page;
    }

    /**
     * checks the route 
     * @return string
     */
    protected function checkRoute($route) { 
       
        $len = strlen($this->routeKeyPrefix);
        //checks if the prefix is already appended to the very last of the route.
        // if so remove it
        if(substr_compare($route,$this->routeKeyPrefix, -$len, $len) == 0) {  
          $route = str_replace($this->routeKeyPrefix, '', $route);
        }
        return $route;
    }


    /**
     * generates the pagination output
     * @return string
     */
    public function serve() {
        if(is_null($this->linkPath)) {
            $route = $this->fw->get('PARAMS.0');
            if($this->fw->exists('PARAMS.'.$this->routeKey))
                $route = preg_replace("/".$this->fw->get('PARAMS.'.$this->routeKey)."$/",'',$route);
            elseif(substr($route,-1) != '/')
            $route.= '/';
        } else
            $route = $this->linkPath;

            $route = $this->checkRoute($route);
           //TODO this is problably not the solution,  but we are getting two page suffixes in the links
             

        $this->fw->set('pg.route',$route);
        $this->fw->set('pg.prefix',$this->routeKeyPrefix);
        $this->fw->set('pg.currentPage',$this->current_page);
        $this->fw->set('pg.nextPage',$this->getNext());
        $this->fw->set('pg.prevPage',$this->getPrev());
        $this->fw->set('pg.firstPage',$this->getFirst());
        $this->fw->set('pg.lastPage',$this->getLast());
        $this->fw->set('pg.rangePages',$this->getInRange());
        $output = \Template::instance()->render($this->template);
        $this->fw->clear('pg');
        return $output;
    }

    /**
     * magic render function for custom tags
     * @static
     * @param $args
     * @return string
     */
    static public function renderTag($args){
        $attr = $args['@attrib'];
        $tmp = Template::instance();
        foreach($attr as &$att)
            $att = $tmp->token($att);
        $pn_code = '$pn = new Pagination('.$attr['items'].');';
        if(array_key_exists('limit',$attr))
            $pn_code .= '$pn->setLimit('.$attr['limit'].');';
        if(array_key_exists('range',$attr))
            $pn_code .= '$pn->setRange('.$attr['range'].');';
        if(array_key_exists('src',$attr))
            $pn_code .= '$pn->setTemplate("'.$attr['src'].'");';
        if(array_key_exists('token',$attr))
            $pn_code .= '$pn->setRouteKey("'.$attr['token'].'");';
        if(array_key_exists('link-path',$attr))
            $pn_code .= '$pn->setLinkPath("'.$attr['link-path'].'");';
        if(array_key_exists('token-prefix',$attr))
            $pn_code .= '$pn->setRouteKeyPrefix("'.$attr['token-prefix'].'");';
        $pn_code .= 'echo $pn->serve();';
        return '<?php '.$pn_code.' ?>';
    }
    
    /**
     * Creates a dropdown box for selecting how many records to show per page.
     *
     * @return  string  The HTML for the limit # input box.
     */
    public function getLimitBox( $selected, $options=array() )
    {
        $limits = !empty($options['limits']) ? $options['limits'] : array(
                json_decode(json_encode(array('value'=>10, 'title'=>10))),
                json_decode(json_encode(array('value'=>25, 'title'=>25))),
                json_decode(json_encode(array('value'=>50, 'title'=>50))),
                json_decode(json_encode(array('value'=>100, 'title'=>100)))
            );
        
        $select_name = !empty($options['name']) ? $options['name'] : 'list[limit]';
        $select_id = !empty($options['id']) ? $options['id'] : 'filter_limit';
        $select_class = !empty($options['class']) ? $options['class'] : 'form-limit-box form-control';
        $select_onchange = !empty($options['onchange']) ? $options['onchange'] : 'onchange="this.form.submit()"';
        $select_data = !empty($options['data']) ? $options['data'] : null;
        
        $html = "<select name='" . $select_name . "' id='" . $select_id . "' class='" . $select_class . "' " . $select_onchange . " " . $select_data . ">";
        foreach ($limits as $limit) 
        {
            $is_selected = null;
            if ($limit->value == $selected) {
                $is_selected = " selected='selected'";
            }
            
            $data = null;
            if (!empty($limit->data)) {
                $data = $limit->data;
            }
            
            $html .= "<option value='" . $limit->value . "'" . $is_selected . " " . $data . ">";
            $html .= $limit->title;
            $html .= "</option>";
        }
        $html .= "</select>";
        
        return $html;
    }
    
    /**
     * Create and return the pagination result set counter string, e.g. Results 1-10 of 42
     *
     * @return  string   Pagination result set counter string.
     */
    public function getResultsCounter()
    {
        $html = null;

        $n = $this->getCurrent() - 1;
        $list_count = ($this->items_per_page + ($this->items_per_page * $n));
        if ($list_count > $this->getItemCount()) {
            $list_count = $this->getItemCount();
        }
        $start = $this->items_per_page * $n + 1;
        if ($start < 0) {
            $start = 0;
        }
        
        if ($start == 0 || $this->getItemCount() == 0) {
            return $html;
        }
        
        $html = $start . " - " . $list_count . " of " . $this->getItemCount();
        
        return $html;
    }
}
?>