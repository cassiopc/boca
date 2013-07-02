/* Copyright 2001 Maratona de Programacao do IME-USP -- cef@ime.usp.br 
 */

#include <stdio.h>

int main(int argc, char *argv[])
{
  FILE * ent;
  int cont = 0;
  int n, num, rec[1000], desp[1000], soma, i, inic, somamax, imax, fmax;
  
  ent = stdin;
  fscanf(ent, "%d", &n);
  while (n > 0){
    cont++;
    printf("Fazenda %d\n", cont);
    for (i = 0; i < n; i++) fscanf(ent, "%d", &rec[i]);
    for (i = 0; i < n; i++) fscanf(ent, "%d", &desp[i]);
    somamax = -1;
    imax = 0;
    fmax = 0;
    soma = 0;
    inic = 1;
    for (i = 0; i < n; i++){
      num = rec[i] - desp[i];
      if (soma + num >= 0){
	soma += num;
        if (soma > somamax){
	  imax = inic;
	  fmax = i + 1;
	  somamax = soma;
	}
      }
      else{
	inic = i + 2;
	soma = 0;
      }
    }
    if(somamax >= 0)
      printf("Inicio %d Fim %d\n\n", imax, fmax);
    else
      printf("O produtor so teve prejuizo nesta fazenda\n\n");
    
    fscanf(ent, "%d", &n);
  }
  return(0);
}

