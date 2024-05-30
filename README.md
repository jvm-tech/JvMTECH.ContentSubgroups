# JvMTECH.ContentSubgroups Package for Neos CMS
[![Latest Stable Version](https://poser.pugx.org/jvmtech/content-subgroups/v/stable)](https://packagist.org/packages/jvmtech/content-subgroups)
[![License](https://poser.pugx.org/jvmtech/content-subgroups/license)](https://packagist.org/packages/jvmtech/content-subgroups)

> Reduce the amount of Content Types (Neos CMS NodeTypes) by creating subgroups and specific migrations to easily switch between them.

- Less clutter in the first step of the ContentCreationDialog
- One Content Type per Fusion Prototype (no layout mixing properties)
- Don't lose data while changing the Content Type or an existing node

### 1. Create shells which are only visible in first ContentCreationDialog step...
```yaml
'Vendor:Subgroup.Image':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Image(s)'
    group: 'general'
  properties:
    targetNodeTypeName:
      ui:
        inspector:
          editorOptions:
            dataSourceAdditionalData:
              contentSubgroup: 'image'

'Vendor:Subgroup.Text':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Text'
    group: 'general'
  properties:
    targetNodeTypeName:
      ui:
        inspector:
          editorOptions:
            dataSourceAdditionalData:
              contentSubgroup: 'text'
```

### 2. Map the actual Content Types to subgroups, selectable in the second ContentCreationDialog step...
```yaml
'Vendor:Content.Image':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Single Image'
    group: 'hidden'
  options:
    contentSubgroup:
      tags:
        - image

'Vendor:Content.ImageSwiper':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Image Swiper'
    group: 'hidden'
  options:
    contentSubgroup:
      tags:
        - image

'Vendor:Content.TextWithImage':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Text with image'
    group: 'hidden'
  options:
    contentSubgroup:
      tags:
        - text
        - image

'Vendor:Content.Bodytext':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Bodytext'
    group: 'hidden'
  options:
    contentSubgroup:
      tags:
        - text

'Vendor:Content.Quote':
  superTypes:
    'Neos.Neos:Content': true
    'JvMTECH.ContentSubgroups:Enable': true
  ui:
    label: 'Quote'
    group: 'hidden'
  options:
    contentSubgroup:
      tags:
        - text
```

### 3. Optionally add property migrations where needed...
```yaml
'Vendor:Content.TextWithImage':
  options:
    contentSubgroup:
      propertyMigrationFrom:
        'Vendor:Content.Bodytext':
          'text':
            'MoveTo': 'imageText'
        'Vendor:Content.Quote':
          'quote':
            'MoveTo': 'imageText'
          'author':
            '/Vendor/Custom/AuthorNodeReferenceToAuthorAssetMigration': 'imageAsset'

'Vendor:Content.Bodytext':
  options:
    contentSubgroup:
      propertyMigrationFrom:
        'Vendor:Content.TextWithImage':
          'imageText':
            'MoveTo': 'quote'
        'Vendor:Content.Quote':
          'quote':
            'MoveTo': 'text'

'Vendor:Content.Quote':
  options:
    contentSubgroup:
      propertyMigrationFrom:
        'Vendor:Content.TextWithImage':
          'imageText':
            'moveTo': 'quote'
        'Vendor:Content.Bodytext':
          'text':
            'MoveTo': 'quote'
          'imageAsset':
            '/Vendor/Custom/ImageAssetToAuthorNodeReference': 'author'
```

## Installation

```
composer require jvmtech/content-subgroups
```

---

by [jvmtech.ch](https://jvmtech.ch)
