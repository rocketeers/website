#!/usr/bin/env bash

# Create stylesheets
npm run build:assets
rm -rf docs/docs/builds
mv public/builds docs/docs/builds

# Copy necessary source files
rm -rf docs/docs/_layouts
cp -r _layouts docs/docs/_layouts
cp docs/rocketeer/*.md docs/docs

# Create Gitbook and copy assets
npm run build:gitbook
cp -r _book/* public
cp public/favicon.png public/gitbook/images/favicon.ico

# Create API docs
rm -rf public/api
npm run build:api

# Cleanup docs folder
cd docs/docs
git reset --hard
git clean -df
