# Prerequisite
- `Lando` setup is required.
# Installation

- Initialize lando for the local development `lando start`
- Install the site Database `lando drush si`
- To get the reset link to login as administrator `lando drush uli`
- Import the configuration `lando drush cim -y`
- - If error occurs we might need to reset the UUID to match with the site UUID
it can be done with `lando drush cset <configuration>` command


# Development
Development etiquettes are described below.
## Git commit format
`git commit -m "ELDrup-001: Initial commit, Module install"`
