<div class="pageRaterStars">
<% with/loop PageRatingResults %>
	<span class="starLabel">Rating:</span>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%;" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_with/loop %>
</div>
