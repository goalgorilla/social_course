# social_course module

Enables CM+ to create courses
by Goal Gorilla ECI team

Initial styles match socialBlue palette

Repo: https://bitbucket.org/goalgorilla/social_course


### Features

- 'All courses' menu item added to the 'Explore' menu
- 'Add course' menu item added to the user menu
- Basic and advanced course group type, with option for sequential or non-sequential
- Section content type with entity reference field for Article and Video content types
- Uses https://www.drupal.org/project/paragraphs for image, text and file content within course
- 'Add Section' button added in sidebar of a course
- Course overview shows status of sections (enrolled, started, finished)

More info to be added here about permissions



### Installing

In your project's composer.json:

```
"require": {
  ...
  "goalgorilla/social_course": "dev-master",
  ...
}
```

```
 "repositories": [
       ...
        {
            "type": "git",
            "url": "git@bitbucket.org:goalgorilla/social_course.git"
        },
        ...
```

### Developer Notes

WIP

### Configuration

WIP


### Intended Results

Update once https://jira.goalgorilla.com/browse/CBI-270 is completed