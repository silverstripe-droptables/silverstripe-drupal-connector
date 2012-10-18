<h2>$Title</h2>

<% if Children %>
	<ul>
		<% loop Children %>
			<li><a href="$Link">$Title</a></li>
		<% end_loop %>
	</ul>
<% end_if %>