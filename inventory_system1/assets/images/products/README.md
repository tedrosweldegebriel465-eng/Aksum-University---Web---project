# Product Images Directory

This directory contains product images for the inventory management system.

## Image Guidelines

### Supported Formats
- JPG/JPEG
- PNG
- GIF

### Recommended Specifications
- **Size**: 400x400px or larger (square format preferred)
- **File Size**: Maximum 5MB
- **Quality**: High resolution for clear display

### Sample Electronics Images

You can add images for the following sample products:

1. **laptop-dell.jpg** - Dell Inspiron Laptop
2. **wireless-mouse.jpg** - Logitech Wireless Mouse  
3. **office-chair.jpg** - Ergonomic Office Chair
4. **printer-paper.jpg** - A4 Printer Paper
5. **usb-drive.jpg** - 32GB USB Flash Drive

### Adding New Images

1. **Via Admin Panel**: Use the product add/edit forms to upload images
2. **Direct Upload**: Place images in this directory and reference them in the database
3. **Bulk Import**: Copy multiple images and update product records

### Image Processing

- Images are automatically resized to fit within 400x400px
- Original aspect ratio is preserved
- Transparency is maintained for PNG/GIF files
- JPEG quality is optimized to 85%

### File Naming Convention

- Use descriptive names: `product-name-variant.jpg`
- Avoid spaces: use hyphens or underscores
- Include product identifier when possible
- Example: `laptop-dell-inspiron-15.jpg`

### Placeholder Image

- **placeholder.jpg** - Default image for products without specific images
- Automatically used when no image is assigned
- Can be customized to match your brand

## Electronics Product Image Suggestions

For a professional inventory system, consider adding images for:

### Computers & Laptops
- Desktop computers
- Laptops (various brands)
- Tablets
- Monitors

### Peripherals
- Keyboards (mechanical, wireless)
- Mice (optical, gaming)
- Webcams
- Headphones/Headsets

### Mobile Devices
- Smartphones
- Phone cases
- Chargers and cables
- Power banks

### Office Equipment
- Printers (inkjet, laser)
- Scanners
- Projectors
- Shredders

### Storage & Memory
- USB drives
- External hard drives
- SD cards
- SSDs

### Networking
- Routers
- Switches
- Cables (Ethernet, HDMI)
- Adapters

## Image Sources

You can obtain product images from:

1. **Manufacturer websites** - Official product photos
2. **Stock photo sites** - Professional product photography
3. **E-commerce sites** - Product listings (ensure proper licensing)
4. **Your own photography** - Custom product shots

## Technical Notes

- Images are stored in `/assets/images/products/`
- Database field: `products.image` (VARCHAR 255)
- Upload handled by: `/admin/upload_image.php`
- Display helper functions available in the system
- Automatic fallback to placeholder for missing images