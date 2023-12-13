<?php

namespace MaplePHP\Nest;

use MaplePHP\Nest\Interfaces\ProtocolInterface;
use MaplePHP\Output\Interfaces\DocumentInterface;
use MaplePHP\Output\Dom\Document;
use MaplePHP\Output\Dom\Element;

class Builder
{
    private $arr;
    private $parent = 0;
    private $permalink;
    private $uri = array();
    private $uriImp;
    private $uriPrepend;
    private $data = array();
    private $item;
    private $currentID;
    private ?ProtocolInterface $protocol = null;
    private $callback;
    private $dom;
    private $level = array();
    private $ul;
    private $li;
    private $ulTag;
    private $ulTagClass = "level-1 clearfix";
    private $liTag;
    private $theUL;
    private $where = array();
    private $maxLevel = 0;
    private $multiple = false;
    private $select = 0;
    private $hideEmpty = false;
    private $count = 0;
    private $nestingSlug = false;

    public function __construct($array)
    {
        $this->arr = $array;
        $this->protocol = new Protocol();
    }

    public function protocol(): ProtocolInterface
    {
        if (!($this->protocol instanceof ProtocolInterface)) {
            throw new \InvalidArgumentException("Protocol instance is missing. It needs to be constructed!", 1);
        }
        return $this->protocol;
    }

    /**
     * Get document (Access DOM instance)
     * @return DocumentInterface
     */
    public function dom(): DocumentInterface
    {
        if (!($this->dom instanceof DocumentInterface)) {
            throw new \InvalidArgumentException("The DocumentInterface (DOM) can not be access before navigation has been built!", 1);
        }
        return $this->dom;
    }

    /**
     * Set start parent
     * @param int $parent
     */
    public function setParent(int $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Enable multi-menu (disbaled by default)
     * @param  bool $bool
     * @return self
     */
    public function setMultiple(bool $bool): self
    {
        $this->multiple = $bool;
        return $this;
    }

    /**
     * Activate nested slug e.g. "/slug1/slug2/slug3" instead of "/slug3"
     * @param  boolean $bool
     * @return self
     */
    public function nestingSlug($bool = true): self
    {
        $this->nestingSlug = $bool;
        return $this;
    }

    /**
     * Set where item
     * @param  array $arr
     * @return self
     */
    public function setWhere(array $arr): self
    {
        $this->where = array_merge($this->where, $arr);
        return $this;
    }

    /**
     * Remove where item
     * @param  string $key
     * @return self
     */
    public function unsetWhere(string $key): self
    {
        $this->where[$key];
        return $this;
    }

    /**
     * Build the navigation
     * @param  callable $callback
     * @return self
     */
    public function build(callable $callback): self
    {
        if ($this->multiple) {
            $c = -1;
            ksort($this->arr);
            foreach ($this->arr as $mid => $_notUsedArr) {
                $this->iterateStructure($mid, $this->parent, $callback, false, $c);
            }
        } else {

            $this->iterateStructure(false, $this->parent, $callback, false, $c);
        }

        return $this;
    }

    /**
     * Get all items
     * @return array
     */
    public function getItems(): array
    {
        if ($this->multiple) {
            return $this->arr[$this->select];
        }
        return $this->arr;
    }

    /**
     * Get all chlid items in parent
     * @param  int $parent
     * @return array|null
     */
    public function getChildren(int $parent): ?array
    {
        $arr = $this->getItems();
        return ($arr[$parent] ?? null);
    }

    /**
     * Get all child items id in parent
     * @param  int $parent
     * @return array
     */
    public function getChildrenID(int $parent): array
    {
        $new = array();
        if ($arr = $this->getChildren($parent)) {
            foreach ($arr as $id => $_notUsedValue) {
                $new[] = (int)$id;
            }
        }
        return $new;
    }

    /**
     * Get last item in parent
     * @param  int $parent
     * @param  array  &$array 
     * @return array
     */
    public function lastChild($parent, array &$array = array()): array
    {
        $arr = $this->getItems();
        if (isset($arr[$parent])) {
            $k = key($arr[$parent]);
            $array = $arr[$parent];
            $this->lastChild($k, $array);
        }
        return $array;
    }

    /**
     * Set max levels
     * @param int $level
     * @return self
     */
    public function setLevel(int $level): self
    {
        $this->maxLevel = $level;
        return $this;
    }

    /**
     * AUto hide empty tag
     * @param  bool|boolean $bool
     * @return self
     */
    public function hideEmpty(bool $bool = true): self
    {
        $this->hideEmpty = $bool;
        return $this;
    }

    /**
     * HTML
     * @param  string   $type   Menu type
     * @param  string   $ulTag
     * @param  string   $liTag
     * @param  callable $callback
     * @return self
     */
    public function html(string $type, string $ulTag, string $liTag, callable $callback): self
    {
        $this->callback[$type]['call'] = $callback;
        $this->callback[$type]['ul'] = $ulTag;
        $this->callback[$type]['li'] = $liTag;
        return $this;
    }

    /**
     * Select menu
     * @param  string|int $key
     * @return self
     */
    public function select($key): self
    {
        $this->select = $key;
        return $this;
    }

    /**
     * Set URL class
     * @param string       $class
     * @param bool|boolean $preserveClass
     * @return self
     */
    public function setClass(string $class, bool $preserveClass = true): self
    {
        if ($preserveClass) {
            $this->ulTagClass .= " {$class}";
        } else {
            $this->ulTagClass = $class;
        }
        return $this;
    }

    /**
     * Count items
     * @param  integer $parent
     * @return int
     */
    public function count($parent = 0): int
    {
        if ($this->multiple) {
            return isset($this->item[$this->select][$parent]) ? count($this->item[$this->select][$parent]) : 0;
        } else {
            return isset($this->item[$parent]) ? count($this->item[$parent]) : 0;
        }
    }

    /**
     * Get sibling
     * @param  int $p
     * @return mixed
     */
    public function siblings($int = 0): mixed
    {
        return $this->item[$int];
    }

    /**
     * Get count
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get navigation
     * @param  string        $type
     * @param  int|integer   $parent
     * @param  callable|null $callback
     * @return string|bool
     */
    public function get(string $type, int $parent = 0, ?callable $callback = null): string|bool
    {


        if (is_null($this->dom)) {
            if (is_null($this->item)) {
                return false;
            }

            if (is_null($this->currentID) && $current = $this->protocol()->getData()) {
                end($current);
                $this->currentID = key($current);
            }


            if ($this->multiple) {
                if (is_null($this->select)) {
                    throw new \Exception("When using multiple menues you also need to select which menu you want to use: @select(0)->get('nav')", 1);
                }
                if (empty($this->item[$this->select])) {
                    throw new \Exception("The multiple menue has wrong menu ID: @select(MENU_ID)->get('nav')", 1);
                }

                $items = $this->item[$this->select];
            } else {
                $items = $this->item;
            }


            $this->ulTag = $this->callback[$type]['ul'];
            $this->liTag = $this->callback[$type]['li'];

            $this->dom = Document::dom($type);
            $this->level[0] = 0;

            $elem = $this->dom->create($this->ulTag);

            if (!($elem instanceof Element)) {
                throw new \Exception("Could not find connection to Element instance", 1);
            }

            $this->ul[0] = $elem->attr("class", "{$this->ulTagClass}");
            $this->theUL = $this->ul[0];


            if (isset($this->callback[$type]['ulCall'])) {
                $this->callback[$type]['ulCall']($this->theUL, false, false, 1);
            }
            $this->iterateView($items, $type, $parent);
            // Reset
            $this->level = array();
            $this->liTag = $this->ulTag = null;
        }

        return $this->dom->execute($callback);
    }

    /**
     * Iterate the view
     * @param  array       $items
     * @param  string      $type
     * @param  int         $parent
     * @param  int|integer $level
     * @param  int|integer $rlvl
     * @return void
     */
    private function iterateView(array $items, string $type, int $parent, int $level = 0, int $rlvl = 1): void
    {
        $rlvl++;
        if (isset($items[$parent])) {
            foreach ($items[$parent] as $id => $obj) {
                if ($parent === 0) {
                    $rlvl = 2;
                }
                if (empty($this->level[$parent])) {
                    $this->level[$parent] = ($level + 1);
                }
                $index = ($this->level[$parent] - 1);
                $active = ((string)$this->currentID === (string)$id) ? " active" : null;
                $levelBool = (($this->maxLevel === 0) || ($rlvl) <= $this->maxLevel);

                if ($this->validateWhere($obj) && (!$this->hideEmpty || ($parent !== 0 || isset($items[$id])))) {
                    if (!$levelBool) {
                        $obj->hasChild = false;
                    }
                    $this->li = $this->ul[$index]->create($this->liTag);
                    call_user_func_array($this->callback[$type]['call'], [$obj, $this->li, $active, ($index + 1), $id, $parent]);

                    if (isset($items[$id])) {
                        $level++;
                        if ($levelBool) {
                            $this->ul[$level] = $this->li->create($this->ulTag)->attr("class", "level-{$rlvl} clearfix");
                            $this->theUL = $this->ul[$level];
                            $this->iterateView($items, $type, $id, $level, $rlvl);
                            if (isset($this->callback[$type]['ulCall'])) {
                                $this->callback[$type]['ulCall']($this->theUL, $obj, $active, $level);
                            }
                        }
                    }
                    $this->count++;
                }
            }
        }
    }

    /**
     * Iterate and build a structure
     * @param  int|false    $mid
     * @param  int          $parent
     * @param  callable     $callback
     * @param  bool|boolean $zeroParent
     * @param  int|null     &$count
     * @return void
     */
    private function iterateStructure(int|false $mid, int $parent, callable $callback, bool $zeroParent = false, ?int &$count = -1): void
    {
        $arr = ($mid !== false) ? $this->arr[$mid] : $this->arr;
        if (isset($arr[$parent])) {
            $siblings = 0;
            foreach ($arr[$parent] as $id => $obj) {
                if ($zeroParent) {
                    $parent = 0;
                }

                $count++;
                // Get and set data
                $permalink = $callback($obj, $id, $parent);

                if ($permalink !== false) {
                    $this->permalink = $permalink;
                    $obj->permaID = $this->permalink;
                    $obj->index = $count;
                    $obj->siblings = $siblings;

                    if (is_array($this->uriPrepend)) {
                        $this->uri = array_merge($this->uriPrepend, $this->uri);
                    }
                    $this->uri[$id] = $this->permalink;
                    $this->uriImp = "/" . (($this->nestingSlug) ? implode("/", $this->uri) : $this->permalink);

                    // Save data
                    $this->data[$id]['data'] = $this->uri;
                    $this->data[$id]['uri'] = $this->uriImp;
                    $this->data[$id]['index'] = $obj->index;

                    $this->protocol()->add($this->uri, $id, $obj);
                }

                $obj->uri = $this->uriImp;
                if ($mid !== false) {
                    $this->item[$mid][$parent][$id] = $obj;
                } else {
                    $this->item[$parent][$id] = $obj;
                }

                array_pop($this->uri);

                $siblings++;
                if (isset($arr[$id])) {
                    $obj->hasChild = true;

                    $this->uri[$id] = $this->permalink;

                    $this->iterateStructure($mid, $id, $callback, $zeroParent, $count);
                } else {
                    $obj->hasChild = false;
                }
            }
            array_pop($this->uri);
        }
    }

    /**
     * Validate where set
     * @param  object $obj
     * @return bool
     */
    private function validateWhere(object $obj): bool
    {
        foreach ($this->where as $k => $v) {
            if (isset($obj->{$k})) {
                if ((string)$obj->{$k} !== (string)$v) {
                    return false;
                }
            }
        }
        return true;
    }

    public function uriAppend(array $array)
    {
        $arr = array();
        foreach ($array as $key => $value) {
            $arr["w{$key}"] = $value;
        }
        $this->uri = array_merge($this->uri, $arr);
        return $this;
    }

    public function uriPrepend(array $array)
    {
        $count = 0;
        $new = array();
        foreach ($array as $uri) {
            $new["nest-prepend-{$count}"] = $uri;
            $count++;
        }
        $this->uriPrepend = $new;
        return $this;
    }
}
