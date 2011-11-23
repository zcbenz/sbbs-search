all: search.html ie6.html

search.html: search.html.in
	sed 's/^[ ]*//g' search.html.in > search.html

ie6.html: ie6.html.in
	sed 's/^[ ]*//g' ie6.html.in > ie6.html
