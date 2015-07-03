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
 
 use Exception;
 
 class Tokenizer
 {
    const STATE_MARKUP = 1;
    const STATE_BEFORE_SCRIPT = 2;
    const STATE_SCRIPT = 3;
    const STATE_AFTER_SCRIPT = 4;
    const STATE_STRING_DQ = 5;
    const STATE_STRING_ESCAPED_DQ = 6;
    const STATE_STRING_SQ = 7;
    const STATE_STRING_ESCAPED_SQ = 8;
    const STATE_EXCLAMATION_POINT = 9;
    const STATE_NOT_EQUAL = 10;
    const STATE_LESS_THAN_SIGN = 11;
    const STATE_SHIFT_LEFT = 12;
    const STATE_LESS_THAN_OR_EQUAL = 13;
    const STATE_GREATER_THAN = 14;
    const STATE_SHIFT_RIGHT = 15;
    const STATE_GREATER_THAN_OR_EQUAL = 16;
    const STATE_ASSIGN = 17;
    const STATE_EQUAL = 18;
    const STATE_DOT = 19;
    const STATE_AND = 20;
    const STATE_OR = 21;
    const STATE_IDENTIFIER = 22;
    
    const T_MARKUP = 1;
    const T_STRING = 2;
    const T_IF = 3;
    const T_FOR = 4;
    const T_AND = 5;
    const T_OR = 6;
    const T_XOR = 7;
    const T_TRUE = 8;
    const T_FALSE = 9;
    const T_IDENTIFIER = 10;
    const T_ENDIF = 11;
    const T_ENDFOR = 12;
    
    protected $tokens;
    protected $file;
    
    protected $map = array(
        'if' => self::T_IF,
        'endif' => self::T_ENDIF,
        'for' => self::T_FOR,
        'endfor' => self::T_ENDFOR,
        'and' => self::T_AND,
        'or' => self::T_OR,
        'xor' => self::T_XOR,
        'true' => self::T_TRUE,
        'false' => self::T_FALSE,
    );
    
    public function __construct($file)
    {
        $this->file = $file;
    }
    
    protected function emitMarkup(&$buffer)
    {
        if($buffer == '')
        {
            return;
        }
        
        $this->tokens[] = array(
            'type' => self::T_MARKUP,
            'value' => $buffer,
        );
        
        $buffer = '';
    }
    
    protected function emitOperator($value)
    {
        $this->tokens[] = array(
            'type' => $value,
        );
    }
    
    protected function emitString(&$value)
    {
        $this->tokens[] = array(
            'type' => self::T_STRING,
            'value' => $value,
        );
        
        $value = '';
    }
    
    protected function emitIdentifier(&$value)
    {
        $identifier = strtolower($value);
        
        if(isset($this->map[$identifier]))
        {
            $this->tokens[] = array(
                'type' => $this->map[$identifier],
                'value' => $identifier,
            );
        }
        else
        {
            $this->tokens[] = array(
                'type' => self::T_IDENTIFIER,
                'value' => $value,
            );
        }
        
        $value = '';
    }
    
    public function tokens()
    {
        
        if($this->tokens !== null)
        {
            return $this->tokens;
        }
        
        $this->tokens = array();
        $content = file_get_contents($this->file);
        $state = self::STATE_MARKUP;
        $buffer = '';
        $allowed;
        
        for($i = 0, $l = strlen($content); $i < $l; $i++)
        {
            $c = $content[$i];
            REPROCESS:
            
            switch($state)
            {
                case self::STATE_MARKUP:
                    if($c == '{')
                    {
                        $state = self::STATE_BEFORE_SCRIPT;
                        continue;
                    }
                    $buffer .= $c;
                    break;
                case self::STATE_BEFORE_SCRIPT:
                    switch($c)
                    {
                        case '{':
                        case '%':
                            $allowed = $c == '{' ? '%' : '}';
                            $this->emitMarkup($buffer);
                            $state = self::STATE_SCRIPT;
                            break;
                        default:
                            $state = self::STATE_MARKUP;
                            $buffer .= '{';
                            goto REPROCESS;
                    }
                    break;
                case self::STATE_SCRIPT:
                    switch($c)
                    {
                        case '%':
                        case '}':
                            if($c == $allowed)
                            {
                                $this->emitOperator($c);
                                continue;
                            }
                            $state = self::STATE_AFTER_SCRIPT;
                            break;
                        case '"':
                            $state = self::STATE_STRING_DQ;
                            break;
                        case "'":
                            $state = self::STATE_STRING_SQ; 
                            break;
                        case '!':
                            $state = self::STATE_EXCLAMATION_POINT;
                            break;
                        case '<':
                            $state = self::STATE_LESS_THAN_SIGN;
                            break;
                        case '>':
                            $state = self::STATE_GREATER_THAN;
                            break;
                        case '=':
                            $state = self::STATE_ASSIGN;
                            break;
                        case '.':
                            $state = self::STATE_DOT;
                            break;
                        case '&':
                            $state = self::STATE_AND;
                            break;
                        case '|':
                            $state = self::STATE_OR;
                            break;
                        case '*':
                        case '+':
                        case '-':
                        case '/':
                        case '?':
                        case ':':
                        case ',':
                        case ';':
                        case '@':
                        case '#':
                        case '$':
                        case '%':
                        case '^':
                        case '~':
                        case '`':
                        case '[':
                        case ']':
                        case '(':
                        case ')':
                            $this->emitOperator($c);
                            break;
                        case " ":
                        case "\t":
                        case "\r":
                        case "\n":
                        case "\0":
                        case "\x0B":
                            continue;
                        case 'A':
                        case 'B':
                        case 'C':
                        case 'D':
                        case 'E':
                        case 'F':
                        case 'G':
                        case 'H':
                        case 'I':
                        case 'J':
                        case 'K':
                        case 'L':
                        case 'M':
                        case 'N':
                        case 'O':
                        case 'P':
                        case 'Q':
                        case 'R':
                        case 'S':
                        case 'T':
                        case 'U':
                        case 'V':
                        case 'W':
                        case 'X':
                        case 'Y':
                        case 'Z':
                        case 'a':
                        case 'b':
                        case 'c':
                        case 'd':
                        case 'e':
                        case 'f':
                        case 'g':
                        case 'h':
                        case 'i':
                        case 'j':
                        case 'k':
                        case 'l':
                        case 'm':
                        case 'n':
                        case 'o':
                        case 'p':
                        case 'q':
                        case 'r':
                        case 's':
                        case 't':
                        case 'u':
                        case 'v':
                        case 'w':
                        case 'x':
                        case 'y':
                        case 'z':
                        case '_':
                            $buffer = $c;
                            $state = self::STATE_IDENTIFIER;
                            break;
                        default:
                            throw new Exception('Invalid character');
                    }
                    break;
                case self::STATE_AFTER_SCRIPT:
                    if($c == '}')
                    {
                        $state = self::STATE_MARKUP;
                        continue;
                    }
                    $this->emitOperator($allowed == '}' ? '%' : '}');
                    $state = self::STATE_SCRIPT;
                    goto REPROCESS;
                    break;
                case self::STATE_STRING_DQ:
                    switch($c)
                    {
                        case '\\':
                            $state = self::STATE_STRING_ESCAPED_DQ;
                            break;
                        case '"':
                            $this->emitString($buffer);
                            $state = self::STATE_SCRIPT;
                            break;
                        default:
                            $buffer .= $c;
                    }
                    break;
                case self::STATE_STRING_ESCAPED_DQ:
                    switch($c)
                    {
                        case '\\':
                            $buffer .= '\\';
                            break;
                        case '"':
                            $buffer .= '"';
                            break;
                        case 'n':
                            $buffer .= "\n";
                            break;
                        case 'r':
                            $buffer .= "\r";
                            break;
                        case 't':
                            $buffer .= "\t";
                            break;
                        case 'f':
                            $buffer .= "\f";
                            break;
                        case '0':
                            $buffer .= "\0";
                            break;
                        default:
                            $buffer .= "\\$c";
                    }
                    $state = self::STATE_STRING_DQ;
                    break;
                case self::STATE_STRING_SQ:echo 'uu';
                    switch($c)
                    {
                        case '\\':
                            $state = self::STATE_STRING_ESCAPED_SQ;
                            break;
                        case "'":
                            $this->emitString($buffer);
                            $state = self::STATE_SCRIPT;
                            break;
                        default:
                            $buffer .= $c;
                    }
                    break;
                case self::STATE_STRING_ESCAPED_SQ:
                    switch($c)
                    {
                        case '\\':
                            $buffer .= '\\';
                            break;
                        case "'":
                            $buffer .= "'";
                            break;
                        default:
                            $buffer .= "\\$c";
                    }
                    break;
                case self::STATE_EXCLAMATION_POINT:
                    switch($c)
                    {
                        case '=':
                            $state = self::STATE_NOT_EQUAL;
                            break;
                        default:
                            $this->emitOperator('!');
                            $state = self::STATE_SCRIPT;
                            goto REPROCESS;
                    }
                    break;
                case self::STATE_NOT_EQUAL:
                    $state = self::STATE_SCRIPT;
                    if($c == '=')
                    {
                        $this->emitOperator('!==');
                        continue;
                    }
                    $this->emitOperator('!=');
                    goto REPROCESS;
                    break;
                case self::STATE_LESS_THAN_SIGN:
                    switch($c)
                    {
                        case '<':
                            $state = self::STATE_SHIFT_LEFT;
                            break;
                        case '=':
                            $state = self::STATE_LESS_THAN_OR_EQUAL;
                            break;
                        default:
                            $state = self::STATE_SCRIPT;
                            $this->emitOperator('<');
                            goto REPROCESS;
                    }
                    break;
                case self::STATE_SHIFT_LEFT:
                    $state = self::STATE_SCRIPT;
                    if($c == '<')
                    {
                        $this->emitOperator('<<<');
                        continue;
                    }
                    $this->emitOperator('<<');
                    goto REPROCESS;
                    break;
                case self::STATE_LESS_THAN_OR_EQUAL:
                    $state = self::STATE_SCRIPT;
                    if($c == '=')
                    {
                        $this->emitOperator('<==');
                        continue;
                    }
                    $this->emitOperator('<=');
                    goto REPROCESS;
                    break;
                case self::STATE_GREATER_THAN:
                    switch($c)
                    {
                        case '>':
                            $state = self::STATE_SHIFT_RIGHT;
                            break;
                        case '=':
                            $state = self::STATE_GREATER_THAN_OR_EQUAL;
                            break;
                        default:
                            $state = self::STATE_SCRIPT;
                            $this->emitOperator('>');
                    }
                    break;
                case self::STATE_SHIFT_RIGHT:
                    $state = self::STATE_SCRIPT;
                    if($c == '>')
                    {
                        $this->emitOperator('>>>');
                        continue;
                    }
                    $this->emitOperator('>>');
                    goto REPROCESS;
                    break;
                case self::STATE_GREATER_THAN_OR_EQUAL:
                    $state = self::STATE_SCRIPT;
                    if($c == '=')
                    {
                        $this->emitOperator('>==');
                        continue;
                    }
                    $this->emitOperator('>=');
                    goto REPROCESS;
                    break;
                case self::STATE_ASSIGN:
                    if($c == '=')
                    {
                        $state = self::STATE_EQUAL;
                        continue;
                    }
                    $state = self::STATE_SCRIPT;
                    $this->emitOperator('=');
                    goto REPROCESS;
                    break;
                case self::STATE_EQUAL:
                    $state = self::STATE_SCRIPT;
                    if($c == '=')
                    {
                        $this->emitOperator('===');
                        continue;
                    }
                    $this->emitOperator('==');
                    goto REPROCESS;
                    break;
                case self::STATE_DOT:
                    $state = self::STATE_SCRIPT;
                    if($c == '.')
                    {
                        $this->emitOperator('..');
                        continue;
                    }
                    $this->emitOperator('.');
                    goto REPROCESS;
                    break;
                case self::STATE_AND:
                    $state = self::STATE_SCRIPT;
                    if($c == '&')
                    {
                        $buffer = 'and';
                        $this->emitIdentifier($buffer);
                        continue;
                    }
                    $this->emitOperator('&');
                    goto REPROCESS;
                    break;
                case self::STATE_OR:
                    $state = self::STATE_SCRIPT;
                    if($c == '|')
                    {
                        $buffer = 'or';
                        $this->emitIdentifier($buffer);
                        continue;
                    }
                    $this->emitOperator('|');
                    goto REPROCESS;
                    break;
                case self::STATE_IDENTIFIER:
                    switch($c)
                    {
                        case 'A':
                        case 'B':
                        case 'C':
                        case 'D':
                        case 'E':
                        case 'F':
                        case 'G':
                        case 'H':
                        case 'I':
                        case 'J':
                        case 'K':
                        case 'L':
                        case 'M':
                        case 'N':
                        case 'O':
                        case 'P':
                        case 'Q':
                        case 'R':
                        case 'S':
                        case 'T':
                        case 'U':
                        case 'V':
                        case 'W':
                        case 'X':
                        case 'Y':
                        case 'Z':
                        case 'a':
                        case 'b':
                        case 'c':
                        case 'd':
                        case 'e':
                        case 'f':
                        case 'g':
                        case 'h':
                        case 'i':
                        case 'j':
                        case 'k':
                        case 'l':
                        case 'm':
                        case 'n':
                        case 'o':
                        case 'p':
                        case 'q':
                        case 'r':
                        case 's':
                        case 't':
                        case 'u':
                        case 'v':
                        case 'w':
                        case 'x':
                        case 'y':
                        case 'z':
                        case '_':
                            $buffer .= $c;
                            break;
                        default:
                            $state = self::STATE_SCRIPT;
                            $this->emitIdentifier($buffer);
                            goto REPROCESS;
                    }
            }
        }
        
        if(self::STATE_MARKUP)
        {
            $this->emitMarkup($buffer);
        }
        
        return $this->tokens;
        
    }
    
 }
 