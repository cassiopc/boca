#!/bin/bash
rm -f keys.txt
../../src/private/createproblemzip.php abacaxi A.problem.zip password | grep -A2 "The following line" | tail -n1 >> keys.txt
../../src/private/createproblemzip.php bits B.problem.zip password | grep -A2 "The following line" | tail -n1 >> keys.txt
../../src/private/createproblemzip.php formiga C.problem.zip password | grep -A2 "The following line" | tail -n1 >> keys.txt
../../src/private/createproblemzip.php multas D.problem.zip password | grep -A2 "The following line" | tail -n1 >> keys.txt
