Page Rater
================================================

This module can add a form to your pages where
visitors can rate the page.

Developer
-----------------------------------------------
Nicolaas Francken [at] sunnysideup.co.nz


Requirements
-----------------------------------------------
see composer.json


Documentation
-----------------------------------------------
Please contact author for more details.

Any bug reports and/or feature requests will be
looked at in detail

We are also very happy to provide personalised support
for this module in exchange for a small donation.


Installation Instructions
-----------------------------------------------
1. Find out how to add modules to SS and add module as per usual.

2. Review configs and add entries to `mysite/_config/config.yml`
(or similar) as necessary.
In the `_config/` folder of this module
you can usually find some examples of config options (if any).

3. add the following to your templates:

$PageRatingForm


# Casted variables

Every Page Rating has the following casted variable:

  - Method (is sometimes null, relates to the method used to calculate score)
  - Stars
  - Percentage
  - RoundedPercentage
  - ReversePercentage
  - ReverseRoundedPercentage
  - StarClass
