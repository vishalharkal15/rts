#!/usr/bin/env python3
"""
FaceNet-based Facial Recognition System for Admin Authentication
Uses TensorFlow/Keras FaceNet model for face embeddings
"""

import os
import sys
import json
import cv2
import numpy as np
import pickle
from datetime import datetime
from pathlib import Path

# Deep Learning libraries
try:
    from keras_facenet import FaceNet
    import tensorflow as tf
except ImportError:
    print("ERROR: Please install required packages:")
    print("pip install keras-facenet tensorflow opencv-python mtcnn")
    sys.exit(1)

from mtcnn import MTCNN

class FaceRecognitionAuth:
    """FaceNet-based facial recognition for admin authentication"""
    
    def __init__(self, db_path="face_data"):
        """
        Initialize the face recognition system
        
        Args:
            db_path: Directory to store face encodings database
        """
        self.db_path = Path(db_path)
        self.db_path.mkdir(parents=True, exist_ok=True)
        
        # Initialize FaceNet model
        print("[INFO] Loading FaceNet model...")
        self.facenet = FaceNet()
        
        # Initialize MTCNN face detector
        print("[INFO] Loading MTCNN face detector...")
        self.detector = MTCNN()
        
        # Load existing face database
        self.face_db_file = self.db_path / "face_encodings.pkl"
        self.admin_db_file = self.db_path / "admin_faces.pkl"
        self.load_database()
        
        print("[INFO] Face recognition system initialized successfully!")
    
    def load_database(self):
        """Load face encodings database from disk"""
        self.face_database = {}
        self.admin_database = {}
        
        if self.face_db_file.exists():
            with open(self.face_db_file, 'rb') as f:
                self.face_database = pickle.load(f)
            print(f"[INFO] Loaded {len(self.face_database)} faces from database")
        
        if self.admin_db_file.exists():
            with open(self.admin_db_file, 'rb') as f:
                self.admin_database = pickle.load(f)
            print(f"[INFO] Loaded {len(self.admin_database)} admin faces from database")
    
    def save_database(self):
        """Save face encodings database to disk"""
        with open(self.face_db_file, 'wb') as f:
            pickle.dump(self.face_database, f)
        
        with open(self.admin_db_file, 'wb') as f:
            pickle.dump(self.admin_database, f)
        
        print(f"[INFO] Database saved successfully!")
    
    def detect_face(self, image):
        """
        Detect face in image using MTCNN
        
        Args:
            image: Input image (BGR format)
            
        Returns:
            Face region (x, y, w, h) or None if no face detected
        """
        # Convert BGR to RGB
        rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        
        # Detect faces
        results = self.detector.detect_faces(rgb_image)
        
        if not results:
            return None
        
        # Get the largest face
        face = max(results, key=lambda x: x['box'][2] * x['box'][3])
        x, y, w, h = face['box']
        
        # Ensure coordinates are positive
        x, y = max(0, x), max(0, y)
        
        return (x, y, w, h)
    
    def extract_face_embedding(self, image, face_box=None):
        """
        Extract face embedding using FaceNet
        
        Args:
            image: Input image
            face_box: Optional (x, y, w, h) tuple, if None will detect automatically
            
        Returns:
            128-dimensional face embedding or None
        """
        # Detect face if not provided
        if face_box is None:
            face_box = self.detect_face(image)
            if face_box is None:
                return None
        
        x, y, w, h = face_box
        
        # Extract face region
        face_img = image[y:y+h, x:x+w]
        
        if face_img.size == 0:
            return None
        
        # Resize to 160x160 (FaceNet input size)
        face_img = cv2.resize(face_img, (160, 160))
        
        # Convert to RGB
        face_img = cv2.cvtColor(face_img, cv2.COLOR_BGR2RGB)
        
        # Normalize
        face_img = face_img.astype('float32')
        mean, std = face_img.mean(), face_img.std()
        face_img = (face_img - mean) / std
        
        # Add batch dimension
        face_img = np.expand_dims(face_img, axis=0)
        
        # Get embedding
        embedding = self.facenet.embeddings(face_img)
        
        return embedding[0]
    
    def compare_faces(self, embedding1, embedding2, threshold=0.6):
        """
        Compare two face embeddings using Euclidean distance
        
        Args:
            embedding1, embedding2: Face embeddings to compare
            threshold: Distance threshold (lower = stricter)
            
        Returns:
            (is_match, distance)
        """
        distance = np.linalg.norm(embedding1 - embedding2)
        is_match = distance < threshold
        return is_match, distance
    
    def register_admin_face(self, admin_id, admin_name, image_source=0, num_samples=5):
        """
        Register a new admin's face
        
        Args:
            admin_id: Unique admin ID
            admin_name: Admin's name
            image_source: Camera index or image path
            num_samples: Number of face samples to collect
            
        Returns:
            Success status and message
        """
        print(f"\n[INFO] Registering admin: {admin_name} (ID: {admin_id})")
        print(f"[INFO] Collecting {num_samples} face samples...")
        print("[INFO] Please look at the camera and move your face slightly")
        
        embeddings = []
        
        # Open camera or load image
        if isinstance(image_source, int):
            cap = cv2.VideoCapture(image_source)
            is_camera = True
        else:
            image = cv2.imread(image_source)
            if image is None:
                return False, "Failed to load image"
            is_camera = False
        
        try:
            sample_count = 0
            
            while sample_count < num_samples:
                if is_camera:
                    ret, frame = cap.read()
                    if not ret:
                        return False, "Failed to capture from camera"
                else:
                    frame = image.copy()
                
                # Detect face
                face_box = self.detect_face(frame)
                
                if face_box is None:
                    if is_camera:
                        cv2.putText(frame, "No face detected!", (50, 50),
                                  cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
                        cv2.imshow('Register Admin Face', frame)
                        if cv2.waitKey(1) & 0xFF == ord('q'):
                            break
                    continue
                
                # Draw rectangle around face
                x, y, w, h = face_box
                cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
                
                # Extract embedding
                embedding = self.extract_face_embedding(frame, face_box)
                
                if embedding is not None:
                    embeddings.append(embedding)
                    sample_count += 1
                    
                    status_text = f"Sample {sample_count}/{num_samples} captured"
                    cv2.putText(frame, status_text, (50, 50),
                              cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
                    print(f"[INFO] {status_text}")
                
                if is_camera:
                    cv2.imshow('Register Admin Face', frame)
                    if cv2.waitKey(1) & 0xFF == ord('q'):
                        break
                else:
                    break  # Single image mode
            
            if is_camera:
                cap.release()
                cv2.destroyAllWindows()
            
            if len(embeddings) < num_samples:
                return False, f"Only captured {len(embeddings)}/{num_samples} samples"
            
            # Calculate average embedding
            avg_embedding = np.mean(embeddings, axis=0)
            
            # Store in admin database
            self.admin_database[admin_id] = {
                'name': admin_name,
                'embedding': avg_embedding,
                'samples': embeddings,
                'registered_at': datetime.now().isoformat()
            }
            
            # Save database
            self.save_database()
            
            return True, f"Admin {admin_name} registered successfully with {len(embeddings)} samples"
        
        except Exception as e:
            if is_camera:
                cap.release()
                cv2.destroyAllWindows()
            return False, f"Error during registration: {str(e)}"
    
    def authenticate_admin(self, image_source=0, threshold=0.6, timeout=10):
        """
        Authenticate admin using facial recognition
        
        Args:
            image_source: Camera index or image path
            threshold: Recognition threshold
            timeout: Timeout in seconds for camera mode
            
        Returns:
            (success, admin_id, admin_name, confidence, message)
        """
        if len(self.admin_database) == 0:
            return False, None, None, 0.0, "No admin faces registered"
        
        print("\n[INFO] Starting admin authentication...")
        print("[INFO] Please look at the camera")
        
        # Open camera or load image
        if isinstance(image_source, int):
            cap = cv2.VideoCapture(image_source)
            is_camera = True
        else:
            image = cv2.imread(image_source)
            if image is None:
                return False, None, None, 0.0, "Failed to load image"
            is_camera = False
        
        try:
            start_time = datetime.now()
            
            while True:
                if is_camera:
                    # Check timeout
                    if (datetime.now() - start_time).seconds > timeout:
                        cap.release()
                        cv2.destroyAllWindows()
                        return False, None, None, 0.0, "Authentication timeout"
                    
                    ret, frame = cap.read()
                    if not ret:
                        break
                else:
                    frame = image.copy()
                
                # Detect face
                face_box = self.detect_face(frame)
                
                if face_box is None:
                    if is_camera:
                        cv2.putText(frame, "No face detected", (50, 50),
                                  cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
                        cv2.imshow('Admin Authentication', frame)
                        if cv2.waitKey(1) & 0xFF == ord('q'):
                            break
                    continue
                
                # Extract embedding
                embedding = self.extract_face_embedding(frame, face_box)
                
                if embedding is None:
                    if is_camera:
                        continue
                    else:
                        return False, None, None, 0.0, "Failed to extract face embedding"
                
                # Compare with all admin faces
                best_match = None
                best_distance = float('inf')
                
                for admin_id, admin_data in self.admin_database.items():
                    stored_embedding = admin_data['embedding']
                    is_match, distance = self.compare_faces(embedding, stored_embedding, threshold)
                    
                    if is_match and distance < best_distance:
                        best_distance = distance
                        best_match = (admin_id, admin_data['name'])
                
                # Draw results
                x, y, w, h = face_box
                
                if best_match:
                    admin_id, admin_name = best_match
                    confidence = max(0, 100 * (1 - best_distance))
                    
                    # Draw green box for authenticated
                    cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
                    text = f"{admin_name} ({confidence:.1f}%)"
                    cv2.putText(frame, text, (x, y-10),
                              cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
                    
                    if is_camera:
                        cv2.imshow('Admin Authentication', frame)
                        cv2.waitKey(2000)  # Show for 2 seconds
                        cap.release()
                        cv2.destroyAllWindows()
                    
                    return True, admin_id, admin_name, confidence, "Authentication successful"
                else:
                    # Draw red box for unauthorized
                    cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 0, 255), 2)
                    cv2.putText(frame, "Unauthorized", (x, y-10),
                              cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 2)
                
                if is_camera:
                    cv2.imshow('Admin Authentication', frame)
                    if cv2.waitKey(1) & 0xFF == ord('q'):
                        break
                else:
                    break  # Single image mode
            
            if is_camera:
                cap.release()
                cv2.destroyAllWindows()
            
            return False, None, None, 0.0, "No matching admin found"
        
        except Exception as e:
            if is_camera:
                cap.release()
                cv2.destroyAllWindows()
            return False, None, None, 0.0, f"Error during authentication: {str(e)}"
    
    def list_admins(self):
        """List all registered admins"""
        return [
            {
                'admin_id': admin_id,
                'name': data['name'],
                'registered_at': data['registered_at'],
                'num_samples': len(data['samples'])
            }
            for admin_id, data in self.admin_database.items()
        ]
    
    def delete_admin(self, admin_id):
        """Delete an admin from the database"""
        if admin_id in self.admin_database:
            del self.admin_database[admin_id]
            self.save_database()
            return True, "Admin deleted successfully"
        return False, "Admin not found"


def main():
    """CLI interface for testing"""
    import argparse
    
    parser = argparse.ArgumentParser(description='FaceNet Admin Authentication System')
    parser.add_argument('action', choices=['register', 'auth', 'list', 'delete'],
                       help='Action to perform')
    parser.add_argument('--admin-id', type=int, help='Admin ID')
    parser.add_argument('--admin-name', help='Admin name')
    parser.add_argument('--camera', type=int, default=0, help='Camera index')
    parser.add_argument('--image', help='Image path for authentication')
    parser.add_argument('--samples', type=int, default=5, help='Number of samples to collect')
    
    args = parser.parse_args()
    
    # Initialize system
    face_auth = FaceRecognitionAuth()
    
    if args.action == 'register':
        if not args.admin_id or not args.admin_name:
            print("ERROR: --admin-id and --admin-name required for registration")
            return
        
        success, message = face_auth.register_admin_face(
            args.admin_id, args.admin_name, args.camera, args.samples
        )
        print(f"\n{'SUCCESS' if success else 'FAILED'}: {message}")
    
    elif args.action == 'auth':
        image_source = args.image if args.image else args.camera
        success, admin_id, admin_name, confidence, message = face_auth.authenticate_admin(image_source)
        
        result = {
            'success': success,
            'admin_id': admin_id,
            'admin_name': admin_name,
            'confidence': confidence,
            'message': message
        }
        print(json.dumps(result, indent=2))
    
    elif args.action == 'list':
        admins = face_auth.list_admins()
        print(f"\nRegistered Admins ({len(admins)}):")
        for admin in admins:
            print(f"  ID: {admin['admin_id']}, Name: {admin['name']}, "
                  f"Samples: {admin['num_samples']}, Registered: {admin['registered_at']}")
    
    elif args.action == 'delete':
        if not args.admin_id:
            print("ERROR: --admin-id required for deletion")
            return
        
        success, message = face_auth.delete_admin(args.admin_id)
        print(f"{'SUCCESS' if success else 'FAILED'}: {message}")


if __name__ == '__main__':
    main()
