<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2015 Marius Sarca
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Template;

use Opis\Template\Tokenizer as T;

class Parser
{
    
    protected $symbols = array();
    protected $current = 0;
    protected $symbol = null;
    protected $max = 0;
    
    public function parse(array $symbols, $func = 'json')
    {
        $this->symbols = $symbols;
        $this->max = count($symbols);
        return $this->{$func}();
    }
    
    protected function accept($symbol)
    {
        if($this->current < $this->max && $this->symbols[$this->current]['type'] === $symbol)
        {
            $this->getSymbol();
            return true;
        }
        
        return false;
    }
    
    protected function error()
    {
        throw new Exception("Unknown symbol");
    }
    
    protected function expect($symbol)
    {
        if($this->accept($symbol))
        {
            return true;
        }
        
        $this->error();
    }
    
    protected function getSymbol()
    {
        $this->symbol = $this->symbols[$this->current++];
    }
    
    protected function printVariable()
    {
        $this->expect(self::TVar);
        
        return array(
            'variable' => $this->tVariable(),
            'filters' => $this->printFilters(),
        );
    }
    
    
    protected function printFilters()
    {
        $filters = array();
        
        while($this->accept(self::GT))
        {
            $filters[] = $this->printFilterDecl();
        }
        
        return $filters;
    }
    
    protected function printFilterDecl()
    {
        $this->expect(self::TVar);
        $name = $this->tVariable();
        
        if($this->accept(self::L_PARAN))
        {
            $arguments = $this->printFilterArgs();
        }
        else
        {
            $arguments = array();
        }
        
        return array(
            'name' => $name,
            'params' => $arguments,
        );
    }
    
    protected function printFilterArgs()
    {
        if($this->accept(self::R_PARAN))
        {
            return array();
        }
        
        $args  = array();
        
        do
        {
            $args[] = $this->printFilterParamValue();
        }
        while($this->accept(self::COMMA));
        
        $this->expect(self::R_PARAN);
        
        return $args;
    }
    
    protected function printFilterParamValue()
    {
        if($this->accept(self::TString))
        {
            return $this->tString();
        }
        elseif($this->accept(self::TBoolean))
        {
            return $this->tBoolean();
        }
        elseif($this->accept(self::TNull))
        {
            return $this->tNull();
        }
        elseif($this->accept(self::TNumber))
        {
            return $this->tNumber();
        }
        elseif($this->accept(self::TVar))
        {
            return $this->tVariable();
        }
        else
        {
            $this->error();
        }
    }
    
    protected function json()
    {
        if($this->accept(self::LC_BRACK))
        {
            $jsonObject = $this->jsonObject();
            return $jsonObject;
        }
        elseif($this->accept(self::LS_BRACK))
        {
            $jsonArray = $this->jsonArray();
            return $jsonArray;
        }
        
        $this->error();
    }
    
    protected function jsonArray()
    {
        if($this->accept(self::RS_BRACK))
        {
            return array();
        }
        
        $array = array();
        
        do
        {
            $array[] = $this->jsonValue();
        }
        while($this->accept(self::COMMA));
        
        $this->expect(self::RS_BRACK);
        
        return $array;
        
    }
    
    protected function jsonObject()
    {
        if($this->accept(self::RC_BRACK))
        {
            return array();
        }
        
        $object = array();
        
        do
        {
            $pair = $this->jsonPair();
            $object[$pair['key']] = $pair['value'];
        }
        while($this->accept(self::COMMA));
        
        $this->expect(self::RC_BRACK);
        
        return $object;
    }
    
    protected function jsonPair()
    {
        $this->expect(self::TString);
        $key = $this->tString();
        $this->expect(self::COLON);
        $value = $this->jsonValue();
        return array('key' => $key, 'value' => $value);
    }
    
    protected function jsonValue()
    {
        if($this->accept(self::LC_BRACK))
        {
            return $this->jsonObject();
        }
        elseif($this->accept(self::LS_BRACK))
        {
            return $this->jsonArray();
        }
        elseif($this->accept(self::TString))
        {
            return $this->tString();
        }
        elseif($this->accept(self::TBoolean))
        {
            return $this->tBoolean();
        }
        elseif($this->accept(self::TNumber))
        {
            return $this->tNumber();
        }
        elseif($this->accept(self::TNull))
        {
            return $this->tNull();
        }
        else
        {
            $this->error();
        }
    }
    
    protected function tString()
    {
        return $this->symbol['value'];
    }
    
    protected function tNumber()
    {
        return $this->symbol['value'];
    }
    
    protected function tNull()
    {
        return null;
    }
    
    protected function tBoolean()
    {
        return $this->symbol['value'] === 'true';
    }
    
    protected function tVariable()
    {
        return $this->symbol['value'];
    }
}
