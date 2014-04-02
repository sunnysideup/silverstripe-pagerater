<% if PageRaterList %>
<ul id="PageRaterList">
	<% with/loop PageRaterList %>
	<li>
		<% include PageRaterStars %>
		<% with/loop Page %><a href="$Link">$MenuTitle</a><% end_with/loop %>
	</li>
	<% end_with/loop %>
</ul>
<% end_if %>
