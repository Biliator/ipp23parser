# ipp23parser

This is a primitive horizontal calendar in Android.

## 📚 Introduction

This is IPP project 1 from 2023.

parse.php is a script that takes a file with an IPPcode23 code on standard input and outputs its XML
representation. The script is divided into three parts. In the first part, it checks the given arguments, in the second part the existence of the
correctness of the header, and in the third the rest of the code.

## 📃 Script

The input is checked first. Whether there is the correct number of arguments and whether the arguments are valid.
In the case of `--help`, a help comment is printed.
If there is a file on the standard input, parse.php checks whether the first valid sequence of characters is
`.IPPcode23`. Otherwise, an error is raised and the return value is 21.
Then the script checks line by line using while. It always splits one line into individual words and stores them in
$code_line field, then in switch it checks if the first value of the field (which represents the first
row value) is something from the allowed instruction set, otherwise an error is raised and the return value is 22.

Each statement in the switch calls a certain function according to the number and type of argument it should have. E.g.
`MOVE <var> <symb>` and `INT2CHAR <var> <symb>` both call the `opVarSymb()` function. The
it then outputs an XML line with the instruction order and opcode and then checks for the correct `<var>` format and
`<symb>` using the functions checkVar($var) and `checkSymb($var)` (in the case of `lable` using
`checkLabel($label)`).
If the format is correct, it increments $ins_order by one and checks with the function
`checkNumPar($param, $n)` whether the following value in `$code_line` is nothing. If so
it isn't, it means there is another invalid piece of text on the line and an error will be thrown and 23 will be returned.
Whenever you check a string or variable, you should always replace the characters `&, < and > with &amp, &lt and &gt`. It
executes the `editVar($type, $var)` function, which returns an already edited string or variable name.
If there is an unknown instruction in the first position of the `$code_line` field, the script is terminated with a return
with a value of 22.

I ran into some problems at work. I solved dividing the line into individual words using the first one
searching for # and then cutting off the rest (this is the comment part). Then, divide as follows:

`$code_line = preg_split('/\s+/', trim($line));`

Where he takes all the empty places.

Another complication was with regular expressions of numbers. Since the script is supposed to take decimal into account,
hexadecimal, binary, and octal systems. It was necessary to create a large number of correct regular
expressions. Fortunately, I was helped by the official PHP documentation, where everything could be obtained.
The script ends when all the lines in the file have been written. Then finally </program> is listed and the script is
finished

## ⚖️ License

See [LICENSE](LICENSE).
