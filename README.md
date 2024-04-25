# ipp23parser

This is a primitive horizontal calendar in Android.

## 📚 Introduction

This is IPP project 1 from 2023.

parse.phh is a script that takes a file with an IPPcode23 code on standard input and outputs its XML representation.
First, the code checks the input to see if it has the correct number of arguments and if the arguments are valid. In the case of --help, a help comment is printed.
If there is a file on the standard input, parse.php checks whether the first valid character sequence is .IPPcode23. Otherwise, an error is raised and the return value is 21. Then the script checks line by line using while. It always splits a single line into the $code_line array into individual words and then checks in a switch if the first value of the array (representing the first value of the line) is something from the allowed instruction set, otherwise an error is raised and the return value is 22.
Each statement in switch calls a certain function according to the number of arguments it should have. E.g.
MOVE <var> <symb> and INT2CHAR <var> <symb> both call the opVarSymb() function. It then writes an XML line with instruction order and opcode and then checks the correct format of <var> and <symb> using the functions checkVar($var) and checkSymb($var) (in the case of lable using checkLabel($label)).
If the format is correct, it increments $ins_order by one and checks with checkNumPar($param, $n) whether the next value in $code_line is just a comment or nothing. If it doesn't, that means there's another invalid piece of code on the line and an error will be thrown and 23 will be returned.
At the same time, it is always necessary to replace the characters &, < and > with &amp, &lt and &gt. This is done by the editVar($type, $var) function, which returns the already edited string.
I ran into some problems at work. I solved splitting the line into individual words by first looking for the # and then cutting off the rest (this is the comment part). Then, divide as follows:
$code_line = preg_split('/\s+/', trim($line));
Where he takes all the empty places.

## ⚖️ License

See [LICENSE](LICENSE).
