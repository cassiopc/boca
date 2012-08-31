/* Copyright 2001 Maratona de Programacao do IME-USP -- cef@ime.usp.br
 */

#include <stdio.h>

typedef struct 
{
  char nome[100];
  char nomeconv[100];
  
  int pontos;
}
m_familia;

#define MAX 21

char conv(char c)
{
  if(c >= 'A' && c <= 'Z') return (c += 'a' - 'A');
  return(c);
  
}


int menor(char v[80], char w[80])
{
  int i=0;

  while(i < 80 && v[i] == w[i])i++;
  return(v[i] < w[i]);
}


int main(int argc, char *argv[])
{
  FILE * ent;
  int cont = 0;
  int n, i,j, multa, nmult, min;
  m_familia familia[20];
  
  ent = stdin;
   
  fscanf(ent, "%d", &n);
  while (n > 0){
    cont++;
    printf("Familia %d\n", cont);
    
    for (i = 0; i < n; i++){
      fscanf(ent, "%s", &(familia[i].nome[0]));
      j = 0;
      while(familia[i].nome[j] != 0) {
	familia[i].nomeconv[j]=conv(familia[i].nome[j]);
        j++;
      }
      familia[i].nomeconv[j]=0;
           
      familia[i].pontos = 0;
    }
    fscanf(ent, "%d", &multa);
    nmult = 0;
    
    while(multa > 0){
      nmult++;
      min = 0;
      for (i = 0; i < n; i++)
	if (familia[i].pontos < familia[min].pontos || 
	    (familia[i].pontos == familia[min].pontos && 
		 menor(familia[i].nomeconv, familia[min].nomeconv))) 
	  min = i;
      printf("Multa %d %s", nmult, familia[min].nome);
      familia[min].pontos += multa;
      if ( familia[min].pontos >= MAX)
	printf(" carteira suspensa\n");
      else printf("\n");
      fscanf(ent, "%d", &multa);
    }
    printf("\n");
    
    fscanf(ent, "%d", &n);
  }
  fclose(ent);
  return(0);
}

	
