#!/usr/bin/env python3
"""
Flask API for FaceNet Facial Recognition
Provides REST endpoints for PHP integration
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import base64
import json
from face_auth import FaceRecognitionAuth
import threading
import time

app = Flask(__name__)
CORS(app)

# Initialize face recognition system
face_auth = FaceRecognitionAuth()

# Thread-safe lock
auth_lock = threading.Lock()


def base64_to_image(base64_string):
    """Convert base64 string to OpenCV image"""
    try:
        # Remove data URL prefix if present
        if ',' in base64_string:
            base64_string = base64_string.split(',')[1]
        
        # Decode base64
        img_data = base64.b64decode(base64_string)
        nparr = np.frombuffer(img_data, np.uint8)
        image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        return image
    except Exception as e:
        print(f"Error decoding base64: {e}")
        return None


def image_to_base64(image):
    """Convert OpenCV image to base64 string"""
    try:
        _, buffer = cv2.imencode('.jpg', image)
        base64_string = base64.b64encode(buffer).decode('utf-8')
        return f"data:image/jpeg;base64,{base64_string}"
    except Exception as e:
        print(f"Error encoding to base64: {e}")
        return None


@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'ok',
        'message': 'FaceNet API is running',
        'admins_registered': len(face_auth.admin_database)
    })


@app.route('/api/register', methods=['POST'])
def register_admin():
    """
    Register a new admin face
    
    Request JSON:
    {
        "admin_id": 1,
        "admin_name": "John Doe",
        "image": "base64_encoded_image"  // or use camera
    }
    """
    try:
        data = request.get_json()
        
        admin_id = data.get('admin_id')
        admin_name = data.get('admin_name')
        image_b64 = data.get('image')
        
        if not admin_id or not admin_name:
            return jsonify({
                'success': False,
                'message': 'admin_id and admin_name are required'
            }), 400
        
        with auth_lock:
            # Check if admin already exists
            if admin_id in face_auth.admin_database:
                return jsonify({
                    'success': False,
                    'message': 'Admin ID already registered'
                }), 400
            
            if image_b64:
                # Use provided image
                image = base64_to_image(image_b64)
                if image is None:
                    return jsonify({
                        'success': False,
                        'message': 'Invalid image data'
                    }), 400
                
                # Save temporary image
                temp_path = f'/tmp/admin_{admin_id}_temp.jpg'
                cv2.imwrite(temp_path, image)
                
                success, message = face_auth.register_admin_face(
                    admin_id, admin_name, temp_path, num_samples=1
                )
            else:
                # Use camera
                success, message = face_auth.register_admin_face(
                    admin_id, admin_name, 0, num_samples=5
                )
        
        return jsonify({
            'success': success,
            'message': message,
            'admin_id': admin_id if success else None
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


@app.route('/api/authenticate', methods=['POST'])
def authenticate():
    """
    Authenticate admin using face recognition
    
    Request JSON:
    {
        "image": "base64_encoded_image"
    }
    """
    try:
        data = request.get_json()
        image_b64 = data.get('image')
        
        if not image_b64:
            return jsonify({
                'success': False,
                'message': 'Image data required'
            }), 400
        
        # Convert base64 to image
        image = base64_to_image(image_b64)
        if image is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image data'
            }), 400
        
        # Save temporary image
        temp_path = '/tmp/auth_temp.jpg'
        cv2.imwrite(temp_path, image)
        
        with auth_lock:
            # Authenticate
            success, admin_id, admin_name, confidence, message = face_auth.authenticate_admin(
                temp_path, threshold=0.6
            )
        
        return jsonify({
            'success': success,
            'admin_id': admin_id,
            'admin_name': admin_name,
            'confidence': round(confidence, 2) if confidence else 0,
            'message': message
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


@app.route('/api/authenticate/camera', methods=['POST'])
def authenticate_camera():
    """
    Authenticate admin using webcam
    Opens camera and performs real-time authentication
    """
    try:
        with auth_lock:
            success, admin_id, admin_name, confidence, message = face_auth.authenticate_admin(
                0, threshold=0.6, timeout=15
            )
        
        return jsonify({
            'success': success,
            'admin_id': admin_id,
            'admin_name': admin_name,
            'confidence': round(confidence, 2) if confidence else 0,
            'message': message
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


@app.route('/api/admins', methods=['GET'])
def list_admins():
    """List all registered admins"""
    try:
        admins = face_auth.list_admins()
        return jsonify({
            'success': True,
            'count': len(admins),
            'admins': admins
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


@app.route('/api/admin/<int:admin_id>', methods=['DELETE'])
def delete_admin(admin_id):
    """Delete an admin from the database"""
    try:
        with auth_lock:
            success, message = face_auth.delete_admin(admin_id)
        
        return jsonify({
            'success': success,
            'message': message
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


@app.route('/api/detect', methods=['POST'])
def detect_face():
    """
    Detect face in image
    
    Request JSON:
    {
        "image": "base64_encoded_image"
    }
    """
    try:
        data = request.get_json()
        image_b64 = data.get('image')
        
        if not image_b64:
            return jsonify({
                'success': False,
                'message': 'Image data required'
            }), 400
        
        # Convert base64 to image
        image = base64_to_image(image_b64)
        if image is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image data'
            }), 400
        
        # Detect face
        face_box = face_auth.detect_face(image)
        
        if face_box is None:
            return jsonify({
                'success': False,
                'message': 'No face detected'
            })
        
        x, y, w, h = face_box
        
        # Draw rectangle on image
        cv2.rectangle(image, (x, y), (x+w, y+h), (0, 255, 0), 2)
        
        # Convert back to base64
        result_image = image_to_base64(image)
        
        return jsonify({
            'success': True,
            'face_box': {'x': int(x), 'y': int(y), 'width': int(w), 'height': int(h)},
            'image': result_image
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500


if __name__ == '__main__':
    print("=" * 60)
    print("FaceNet Facial Recognition API Server")
    print("=" * 60)
    print(f"Registered admins: {len(face_auth.admin_database)}")
    print("\nAPI Endpoints:")
    print("  GET  /api/health           - Health check")
    print("  POST /api/register         - Register admin face")
    print("  POST /api/authenticate     - Authenticate with image")
    print("  POST /api/authenticate/camera - Authenticate with camera")
    print("  GET  /api/admins           - List registered admins")
    print("  DELETE /api/admin/<id>     - Delete admin")
    print("  POST /api/detect           - Detect face in image")
    print("\nStarting server on http://0.0.0.0:5000")
    print("=" * 60)
    
    app.run(host='0.0.0.0', port=5000, debug=True, threaded=True)
