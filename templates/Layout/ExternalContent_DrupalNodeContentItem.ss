<h2>$Title</h2>

$Body

<% if Children %>
	<h3>Child Pages:</h3>
	<ul>
		<% loop Children %>
			<li><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>
<% end_if %>