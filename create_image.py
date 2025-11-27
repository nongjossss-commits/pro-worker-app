from PIL import Image
import os

img = Image.new('RGB', (200, 200), color = 'red')
img.save('test_image.jpg')
print("Created test_image.jpg")
