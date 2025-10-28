#!/bin/bash

# FaceNet Face Recognition Setup Script
# This script automates the installation and setup process

set -e

echo "========================================="
echo "FaceNet Face Recognition Setup"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running in correct directory
if [ ! -f "face_auth.py" ]; then
    echo -e "${RED}Error: Please run this script from the face_recognition directory${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking Python installation...${NC}"
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}Python3 not found! Please install Python 3.8 or higher.${NC}"
    exit 1
fi

PYTHON_VERSION=$(python3 --version | cut -d' ' -f2 | cut -d'.' -f1,2)
echo -e "${GREEN}✓ Python $PYTHON_VERSION found${NC}"

echo ""
echo -e "${YELLOW}Step 2: Installing Python dependencies...${NC}"
echo "This may take several minutes..."

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo -e "${RED}pip3 not found! Installing...${NC}"
    sudo apt-get update
    sudo apt-get install -y python3-pip
fi

# Install requirements
pip3 install -r requirements.txt --user

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Python dependencies installed successfully${NC}"
else
    echo -e "${RED}Failed to install dependencies. Please check the error above.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Setting up database...${NC}"

# Check if MySQL is running
if ! sudo /opt/lampp/bin/mysql -u root -e "SELECT 1" &> /dev/null; then
    echo -e "${RED}MySQL/MariaDB not accessible. Make sure XAMPP is running:${NC}"
    echo "  sudo /opt/lampp/lampp start"
    exit 1
fi

# Run database migration
sudo /opt/lampp/bin/mysql -u root rts_ticket_system < database_migration.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database schema updated successfully${NC}"
else
    echo -e "${RED}Failed to update database. Check MySQL logs.${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 4: Creating face data directory...${NC}"
mkdir -p face_data
echo -e "${GREEN}✓ Face data directory created${NC}"

echo ""
echo -e "${YELLOW}Step 5: Testing installation...${NC}"

# Test imports
python3 -c "
import tensorflow as tf
from keras_facenet import FaceNet
import cv2
from mtcnn import MTCNN
print('✓ All Python modules loaded successfully')
" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Installation test passed${NC}"
else
    echo -e "${RED}Installation test failed. Some modules may be missing.${NC}"
    exit 1
fi

echo ""
echo "========================================="
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Start the Face Recognition API:"
echo -e "   ${YELLOW}python3 face_api.py${NC}"
echo ""
echo "2. Open in browser:"
echo -e "   ${YELLOW}http://localhost/rts/admin_face_management.php${NC}"
echo ""
echo "3. Register your admin face in the management panel"
echo ""
echo "4. Try facial recognition login:"
echo -e "   ${YELLOW}http://localhost/rts/admin_face_login.php${NC}"
echo ""
echo "For detailed documentation, see README.md"
echo ""
