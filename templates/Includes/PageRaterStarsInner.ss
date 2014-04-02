<% if PageHasBeenRatedByUser %>
<div class="pageRaterStars">
<% with/loop CurrentUserRating %>
	<label class="starLabel">Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_with/loop %>
</div>
<% else %>
<div class="pageRaterStars">
<% with/loop PageRatingResults %>
	<label class="starLabel">Average Rating:</label>
	<div class="stars">
		<div style="width: {$RoundedPercentage}%" class="stars-bg"></div>
		<img alt="$Stars stars" src="pagerater/images/stars.png" />
	</div>
<% end_with/loop %>
</div>
<% end_if %>
