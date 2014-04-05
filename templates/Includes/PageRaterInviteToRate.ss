<div class="pageRaterStars">
<% loop PageRatingResults %>
	<span class="starLabel">Rating:</span>
	<div class="stars">
		<a href="$Parent.Link#Form_PageRatingForm">
			<img alt="$Stars stars" src="pagerater/images/stars.png" title="be the first to rate &quot;{$Parent.Title.ATT}&quot;" />
		</a>
	</div>
<% end_loop %>
</div>
