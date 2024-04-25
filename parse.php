<?php
ini_set('display_errors', 'stderr');

# cheack if '--help' then print help
if ($argc < 3) 
{
    if ($argc == 2)
    {
        if (!strcmp($argv[1], "--help"))
        { 
            echo("\n\nSkript typu filtr načte ze standardního vstupu zdrojový kód v IPPcode23, zkontroluje lexikální a 
            syntaktickou správnost kódu a vypíše na standardní výstup XML reprezentaci programu.\n\n");
            echo("Použití: php8.1 parse.php [--help] < file\n");
            echo("  --help      Vypíše nápovědu\n");
            echo("  file        Název vstupného souboru s IPPcode23 kódem\n\n");
            echo("Chyby v kódu:\n");
            echo("  21 - chybná nebo chybějící hlavička ve zdrojovém kódu zapsaném v IPPcode23\n");
            echo("  22 - neznámý nebo chybný operační kód ve zdrojovém kódu zapsaném v IPPcode23\n");
            echo("  23 - jiná lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode23\n\n");
            exit(0);
        }
        else
        {
            echo("Unknown argument!\n");
            exit(10);
        }
    }
}
else
{
    echo("Wrong number of arguments!\n");
    exit(10);
}

$ins_order = 1;
$header = false;

# check if header exits
while ($header == false && $line = fgets(STDIN))
{
    $cut = strpos($line, '#');
    if ($cut !== false)
        $line = substr($line, 0, $cut);

    $code_line = preg_split('/\s+/', trim($line));
    if (!strcmp(trim($code_line[0]), ".IPPcode23"))
    {
        echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
        echo("<program language=\"IPPcode23\">\n");
        $header = true;
        checkNumOfPar($code_line, 1);
    }
    elseif (substr($code_line[0], 0, 1) != "")
    {
        echo("Missing or wrong header \".IPPcode23\"!\n");
        exit(21);
    }
}

if ($header == false)
{
    echo("Missing or wrong header \".IPPcode23\"!\n");
    exit(21);
}

# starts taking line by line check if the line is in the right format
# removes everything after #, splits line and saves it into $code_line array
# case sorts all instructions depending on the number of arguments they require
while ($line = fgets(STDIN))
{
    $cut = strpos($line, '#');
    if ($cut !== false)
        $line = substr($line, 0, $cut);
    $code_line = preg_split('/\s+/', trim($line)); 

    switch(strtoupper($code_line[0]))
    {
        # OP
        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            noArg();
            break;

         # OP <symb>
        case 'WRITE':
        case 'EXIT':
        case 'PUSHS':
        case 'DPRINT':
            opSymb();
            break;
        
        # OP <var>
        case 'DEFVAR':
        case 'POPS':
            opVar();
            break;
            
        # OP <label>
        case 'LABEL':
        case 'JUMP':
        case 'CALL':
            opLabel();
            break;

        # OP <var> <symb>
        case 'MOVE':
        case 'INT2CHAR':
        case 'NOT':
        case 'STRLEN':
        case 'TYPE':
            opVarSymb();
            break;

        # OP <var> <symb1> <symb2>
        case 'ADD':
        case 'SUB':
        case 'MUL':
        case 'IDIV':
        case 'LT':
        case 'GT':
        case 'EQ':
        case 'AND':
        case 'OR':
        case 'STRI2INT':
        case 'CONCAT':
        case 'GETCHAR':
        case 'SETCHAR':
            opVarSymbSymb();
            break;
        
        # OP ⟨label⟩ ⟨symb1⟩ ⟨symb2⟩ 
        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            opLabelSymbSymb();
            break;
        
        # OP <var> <type>
        case 'READ':
            opRead();
            break;
             
        default:
            if ($code_line[0] != '')
            {
                echo("Wrong operation code!\n");
                exit(22);
            }
            break;
    }
}

echo("</program>\n");

# check for situation: PROCEDURE
function noArg()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");
    $ins_order++;
    checkNumOfPar($code_line, 1);
    echo("\t</instruction>\n");
}

# check for situation: INSTRUCTION <symb>
function opSymb()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");
    
    $type = checkSymb($code_line[1]);
    if ($type != "none")
    {
        $var = $code_line[1];
        $var = edditVar($type, $var);
        echo("\t\t<arg1 type=\"".$type."\">".trim($var)."</arg1>\n"); 
        $ins_order++;
        checkNumOfPar($code_line, 2);
        echo("\t</instruction>\n");
    }
    else
    {
        echo("Wrong or missing <symb> for instruction ".$code_line[0]."!\n"); 
        exit(23);
    }
}

# check for situation: INSTRUCTION <var>
function opVar()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");
    if (checkVar($code_line[1]))
    {
        $var3 = edditVar("var", $code_line[1]);
        echo("\t\t<arg1 type=\"var\">".trim($var3)."</arg1>\n"); 
        $ins_order++;
        checkNumOfPar($code_line, 2);
        echo("\t</instruction>\n");
    }
    else
    {
        echo("Wrong or missing <var> for instruction DEFVAR!\n");
        exit(23);
    }
}

# check for situation: INSTRUCTION <label>
function opLabel()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");

    if (checkLabel($code_line[1]))
    {
        echo("\t\t<arg1 type=\"label\">".trim($code_line[1])."</arg1>\n"); 
        $ins_order++;
        checkNumOfPar($code_line, 2);
        echo("\t</instruction>\n");
    }
    else
    {
        echo("Wrong or missing <label> for instruction LABEL!\n");
        exit(23);
    }
}

# check for situation: INSTRUCTION <var> <symb>
function opVarSymb()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");

    if (checkVar($code_line[1]))
    {
        $var3 = edditVar("var", $code_line[1]);
        echo("\t\t<arg1 type=\"var\">".$var3."</arg1>\n");
        $type = checkSymb($code_line[2]);
        if ($type != "none")
        {
            $var = $code_line[2];
            $var = edditVar($type, $var);
            echo("\t\t<arg2 type=\"".$type."\">".$var."</arg2>\n");
            $ins_order++;
            checkNumOfPar($code_line, 3);
            echo("\t</instruction>\n");
        }
        else
        {
            echo("Wrong or missing <symb> for instruction MOVE!\n"); 
            exit(23);
        }
    }
    else
    {
        echo("Wrong or missing <var> for instruction MOVE!\n");
        exit(23);
    }
}

# check for situation: INSTRUCTION <var> <symb1> <symb2>
function opVarSymbSymb()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");

    if (checkVar($code_line[1]))
    {
        $var3 = edditVar("var", $code_line[1]);
        echo("\t\t<arg1 type=\"var\">".$var3."</arg1>\n");
        $type = checkSymb($code_line[2]);
        $type2 = checkSymb($code_line[3]);
        if ($type != "none" && $type2 != "none")
        {
            $var = $code_line[2];
            $var2 = $code_line[3];
            $var = edditVar($type, $var);
            $var2 = edditVar($type2, $var2);
            echo("\t\t<arg2 type=\"".$type."\">".$var."</arg2>\n");
            echo("\t\t<arg3 type=\"".$type2."\">".$var2."</arg3>\n");
            $ins_order++;
            checkNumOfPar($code_line, 4);
            echo("\t</instruction>\n");
        }
        else
        {
            echo("Wrong or missing <symb> for instruction ".$code_line[0]."!\n"); 
            exit(23);
        }
    }
    else
    {
        echo("Wrong or missing <var> for instruction ".$code_line[0]."!\n");
        exit(23);
    }
}

# check for situation: INSTRUCTION <label> <symb1> <symb2>
function opLabelSymbSymb()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");
    if (checkLabel($code_line[1]))
    {
        echo("\t\t<arg1 type=\"label\">".$code_line[1]."</arg1>\n");
        $type = checkSymb($code_line[2]);
        $type2 = checkSymb($code_line[3]);
        if ($type != "none" && $type2 != "none")
        {
            $var = $code_line[2];
            $var2 = $code_line[3];
            $var = edditVar($type, $var);
            $var2 = edditVar($type2, $var2);
            echo("\t\t<arg2 type=\"".$type."\">".$var."</arg2>\n");
            echo("\t\t<arg3 type=\"".$type2."\">".$var2."</arg3>\n");
            $ins_order++;
            checkNumOfPar($code_line, 4);
            echo("\t</instruction>\n");
        }
        else
        {
            echo("Wrong or missing <symb> for instruction ".$code_line[0]."!\n"); 
            exit(23);
        }
    }
    else
    {
        echo("Wrong or missing <label> for instruction ".$code_line[0]."!\n");
        exit(23);
    }
}

# check for situation: INSTRUCTION <var> <type>
function opRead()
{
    global $code_line, $ins_order;
    echo("\t<instruction order=\"".$ins_order."\" opcode=\"".strtoupper($code_line[0])."\">\n");
    if (checkVar($code_line[1]))
    {
        $var = edditVar("var", $code_line[1]);
        echo("\t\t<arg1 type=\"var\">".$var."</arg1>\n");
        if (preg_match("/^[a-zA-Z][a-zA-Z0-9]*$/", $code_line[2]))
        {
            echo("\t\t<arg2 type=\"type\">".$code_line[2]."</arg2>\n");
            $ins_order++;
            checkNumOfPar($code_line, 3);
            echo("\t</instruction>\n");
        }
        else
        {
            echo("Wrong or missing <type> for instruction READ!\n"); 
            exit(23);
        }
    }
    else
    {
        echo("Wrong or missing <var> for instruction READ!\n");
        exit(23);
    }
}

# This function replace '&', '<' and '>' with '&amp;', '&lt;', '&gt;' in string
# $type - var/string, $var - value to eddit
# @return string
function edditVar($type, $var)
{
    if ($type != "var") $var = substr($var, strpos($var, '@') + 1, strlen($var) - 1);
    if ($type == "string" || $type == "var") 
    {
        $var = str_replace("&", "&amp;", $var);
        $var = str_replace("<", "&lt;", $var);
        $var = str_replace(">", "&gt;", $var); 
    } 
    return $var;
}

# check if the number of param on the line is right
function checkNumOfPar($param, $n)
{
    if (sizeof($param) > $n)
        if ($param[$n] != "")        
        {
            echo("Wrong number of param!\n");
            exit(23);
        }
}

# check Regex of <symb>
# int, bool, nil, string or var
# $var - value to check
# # @return string - type of var
function checkSymb($var)
{
    $correct = false;
    $type = "none";


    if (preg_match("/^int@[+-]?[1-9][0-9]*(_[0-9]+)*$/", $var)) $correct = true;
    elseif (preg_match("/^int@0/", $var)) $correct = true;
    elseif (preg_match("/^int@0[xX][0-9a-fA-F]+(_[0-9a-fA-F]+)*$/", $var)) $correct = true;
    elseif (preg_match("/^int@0[oO]?[0-7]+(_[0-7]+)*$/", $var)) $correct = true;
    elseif (preg_match("/^int@0[bB][01]+(_[01]+)*$/", $var)) $correct = true;
    elseif (preg_match("/^bool@(true|false)$/", $var)) $correct = true;
    elseif (preg_match("/^nil@nil$/", $var)) $correct = true;
    elseif (preg_match("/^string@*/", $var))
    {
        preg_match_all("/\\\[0-9]{3}/", $var, $matches1);
        preg_match_all("/\\\[0-9]*/", $var, $matches2);
        if (count($matches1[0]) == count($matches2[0]))
            $correct = true;
    } 
    if ($correct)
    {
        $type = substr($var, 0, strpos($var, '@'));
    }
    elseif (preg_match("/^(LF|GF|TF)@[a-zA-Z_$\-&@%*!?][a-zA-Z_$\-&@%*!?0-9]*$/", $var))
    {
        $correct = true;
        $type = "var";
    }
    return $type;
}

# check Regex of <var>
# $var - value to check
# @return bool 
function checkVar($var)
{
    return preg_match("/(LF|GF|TF)@[a-zA-Z_$-&%*!?][a-zA-Z_$\-&%*!?0-9]*/", $var);
}

# check if label's Regex is right format 
# $label - label to check
# @return bool
function checkLabel($label)
{
    return preg_match("/^[a-zA-Z_$&\-%*!?][a-zA-Z_$\-&%*!?0-9]*$/", $label);
}