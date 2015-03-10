%option noyywrap nodefault

%x MONTH DAY HOUR MIN SEC

%{
	#include <stdlib.h>
	#include "tokens.h"

	int pri_len;
	static int month;
	static const unsigned char num_days[] = { 0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 };
%}

%%
^<(0|([1-9][0-9]?[0-9]?))>			{ pri_len = strlen (yytext); BEGIN MONTH; }
.|\n								return TOK_BAD_PRI;
<MONTH>"Jan "						{ month =  1; BEGIN DAY; }
<MONTH>"Feb "						{ month =  2; BEGIN DAY; }
<MONTH>"Mar "						{ month =  3; BEGIN DAY; }
<MONTH>"Apr "						{ month =  4; BEGIN DAY; }
<MONTH>"May "						{ month =  5; BEGIN DAY; }
<MONTH>"Jun "						{ month =  6; BEGIN DAY; }
<MONTH>"Jul "						{ month =  7; BEGIN DAY; }
<MONTH>"Aug "						{ month =  8; BEGIN DAY; }
<MONTH>"Sep "						{ month =  9; BEGIN DAY; }
<MONTH>"Oct "						{ month = 10; BEGIN DAY; }
<MONTH>"Nov "						{ month = 11; BEGIN DAY; }
<MONTH>"Dec "						{ month = 12; BEGIN DAY; }
<MONTH>.|\n							return TOK_BAD_TIMESTAMP;
<DAY>(" "[1-9]" ")|([1-3][0-9]" ")	{ if (atoi (yytext) > num_days[month]) return TOK_BAD_TIMESTAMP; else BEGIN HOUR; }
<DAY>.|\n							return TOK_BAD_TIMESTAMP;
<HOUR>[0-2][0-9]:					{ if (atoi (yytext) > 23) return TOK_BAD_TIMESTAMP; else BEGIN MIN; }
<HOUR>.|\n							return TOK_BAD_TIMESTAMP;
<MIN>[0-5][0-9]:					{ if (atoi (yytext) > 59) return TOK_BAD_TIMESTAMP; else BEGIN SEC; }
<MIN>.|\n							return TOK_BAD_TIMESTAMP;
<SEC>[0-6][0-9]" "					{ if (atoi (yytext) > 61) return TOK_BAD_TIMESTAMP; else return TOK_HEADER_OK; }
<SEC>.|\n							return TOK_BAD_TIMESTAMP;
%%