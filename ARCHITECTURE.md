# Architecture: productcomments

## Purpose

A PrestaShop module that adds a customer product review and rating system. Customers can
leave comments with star ratings and per-criterion grades. Supports moderation, usefulness
voting, and comment reporting.

## Directory Structure

```
productcomments.php           # Main module class; registers hooks and installs tables
Product_Comment.php           # Legacy ObjectModel for product comments (PS ObjectModel layer)
Product_Comment_Criterion.php  # Legacy ObjectModel for rating criteria

src/
  Entity/
    Product_Comment.php                   # Doctrine entity for a product comment
    Product_Comment_Criterion.php         # Doctrine entity for a rating criterion
    Product_Comment_Criterion_Lang.php    # Translations for criterion labels
    Product_Comment_Grade.php             # Grade given by a comment for a criterion
    Product_Comment_Report.php            # Report of an abusive comment
    Product_Comment_Usefulness.php        # "Was this helpful?" votes
  Form/
    Product_Comment_Criterion_Form_Data_Handler.php   # Handles criterion form persistence
    Product_Comment_Criterion_Form_Data_Provider.php  # Provides form data for editing
  Repository/
    Product_Comment_Criterion_Repository.php  # Doctrine repository for criteria
    Product_Comment_Repository.php            # Doctrine repository for comments

controllers/front/
  Post_Comment.php             # AJAX endpoint: submit a new comment
  List_Comments.php            # AJAX endpoint: paginated comment listing
  Comment_Grade.php            # AJAX endpoint: record criterion grades
  Report_Comment.php           # AJAX endpoint: report an abusive comment
  Update_Comment_Usefulness.php  # AJAX endpoint: record usefulness vote

views/templates/               # Smarty templates for front-office comment display
upgrade/                       # Database migration scripts
translations/                  # Module translation files
```

## Key Design Decisions

### Dual ObjectModel + Doctrine Architecture

Legacy `Product_Comment.php` and `Product_Comment_Criterion.php` in the root use
PrestaShop's `ObjectModel` pattern for backwards compatibility. New code in `src/Entity/`
uses Doctrine ORM entities and repositories, which align with PrestaShop's modern
architecture.

### AJAX-Based Comment Interaction

All interactive operations (posting, grading, reporting, voting) are handled via dedicated
front-office AJAX controllers that return JSON. This keeps the module's UX fully dynamic
without full-page reloads.

### Moderation Flow

New comments are held in a pending state until approved in the back office via the
module's dedicated admin interface (accessible from the Products tab).

## Extension Points

- Hook `actionProductCommentAdd` after a new comment is posted.
- Override templates in the active theme's `modules/productcomments/` directory.
