# Social Course

Enables CM+ to create courses

### Features

- 'All courses' menu item added to the 'Explore' menu
- 'Add course' menu item added to the user menu
- Basic and advanced course group type, with option for sequential or non-sequential
- Section content type with entity reference field for Article and Video content types
- Uses https://www.drupal.org/project/paragraphs for image, text and file content within course
- 'Add Section' button added in sidebar of a course
- Course overview shows status of sections (enrolled, started, finished)
- Initial styles match socialblue palette

More info to be added here about permissions

### Installation

In your project's composer.json:

```
"require": {
  ...
  "goalgorilla/social_course": "^1.0",
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

### Documentation

Update once https://jira.goalgorilla.com/browse/ECI-676 is completed