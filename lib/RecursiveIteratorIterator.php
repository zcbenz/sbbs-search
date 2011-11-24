<?php

class RecursiveIteratorIterator implements OuterIterator
{
    const LEAVES_ONLY       = 0;
    const SELF_FIRST        = 1;
    const CHILD_FIRST       = 2;

    const CATCH_GET_CHILD   = 0x00000002;

    private $ait = array();
    private $count = 0;
    private $mode  = self::LEAVES_ONLY;
    private $flags = 0;

    function __construct(RecursiveIterator $it, $mode = self::LEAVES_ONLY, $flags = 0)
    {
        $this->ait[0] = $it;
        $this->mode   = $mode;
        $this->flags  = $flags;
    }

    function rewind()
    {
        while ($this->count) {
            unset($this->ait[$this->count--]);
            $this->endChildren();
        }
        $this->ait[0]->rewind();
        $this->ait[0]->recursed = false;
        callNextElement(true);
    }
    
    function valid()
    {
        $count = $this->count;
        while ($count) {
            $it = $this->ait[$count];
            if ($it->valid()) {
                return true;
            }
            $count--;
            $this->endChildren();
        }
        return false;
    }
    
    function key()
    {
        $it = $this->ait[$this->count];
        return $it->key();
    }
    
    function current()
    {
        $it = $this->ait[$this->count];
        return $it->current();
    }
    
    function next()
    {
        while ($this->count) {
            $it = $this->ait[$this->count];
            if ($it->valid()) {
                if (!$it->recursed && callHasChildren()) {
                    $it->recursed = true;
                    try
                    {
                        $sub = callGetChildren();
                    }
                    catch (Exception $e)
                    {
                        if (!($this->flags & self::CATCH_GET_CHILD))
                        {
                            throw $e;
                        }
                        $it->next();
                        continue;
                    }
                    $sub->recursed = false;
                    $sub->rewind();
                    if ($sub->valid()) {
                        $this->ait[++$this->count] = $sub;
                        if (!$sub instanceof RecursiveIterator) {
                            throw new Exception(get_class($sub).'::getChildren() must return an object that implements RecursiveIterator');
                        }
                        $this->beginChildren();
                        return;
                    }
                    unset($sub);
                }
                $it->next();
                $it->recursed = false;
                if ($it->valid()) {
                    return;
                }
                $it->recursed = false;
            }
            if ($this->count) {
                unset($this->ait[$this->count--]);
                $it = $this->ait[$this->count];
                $this->endChildren();
                callNextElement(false);
            }
        }
        callNextElement(true);
    }

    function getSubIterator($level = NULL)
    {
        if (is_null($level)) {
            $level = $this->count;
        }
        return @$this->ait[$level];
    }

    function getInnerIterator()
    {
        return $this->it;
    }

    function getDepth()
    {
        return $this->level;
    }

    function callHasChildren()
    {
        return $this->ait[$this->count]->hasChildren();
    }

    function callGetChildren()
    {
        return $this->ait[$this->count]->getChildren();
    }

    function beginChildren()
    {
    }
    
    function endChildren()
    {
    }

    private function callNextElement($after_move)
    {
        if ($this->valid())
        {
            if ($after_move)
            {
                if (($this->mode == self::SELF_FIRST && $this->callHasChildren())
                ||   $this->mode == self::LEAVES_ONLY)
                $this->nextElement();
            }
            else
            {
                $this->nextElement();
            }
        }
    }
    
    function nextElement()
    {
    }
}

?>
