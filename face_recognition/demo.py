#!/usr/bin/env python3
"""
Demo script for FaceNet Admin Authentication
This script demonstrates the core functionality
"""

import sys
import time
from face_auth import FaceRecognitionAuth

def print_banner():
    print("=" * 70)
    print("FaceNet Admin Authentication System - Demo")
    print("=" * 70)
    print()

def demo_menu():
    print("\nDemo Menu:")
    print("1. Register a test admin face")
    print("2. Authenticate with registered face")
    print("3. List all registered admins")
    print("4. Test face detection only")
    print("5. Exit")
    print()
    return input("Select option (1-5): ").strip()

def main():
    print_banner()
    
    # Initialize system
    print("[INFO] Initializing FaceNet authentication system...")
    try:
        face_auth = FaceRecognitionAuth()
        print("[SUCCESS] System initialized!\n")
    except Exception as e:
        print(f"[ERROR] Failed to initialize: {e}")
        sys.exit(1)
    
    while True:
        choice = demo_menu()
        
        if choice == '1':
            # Register admin
            print("\n--- REGISTER ADMIN FACE ---")
            admin_id = input("Enter admin ID (e.g., 999): ").strip()
            admin_name = input("Enter admin name: ").strip()
            
            if not admin_id or not admin_name:
                print("[ERROR] Invalid input")
                continue
            
            try:
                admin_id = int(admin_id)
            except:
                print("[ERROR] Admin ID must be a number")
                continue
            
            print("\n[INFO] Starting registration...")
            print("[INFO] Please look at your camera")
            print("[INFO] The system will capture 5 samples")
            print("[INFO] Move your head slightly between captures\n")
            
            time.sleep(2)
            
            success, message = face_auth.register_admin_face(
                admin_id, admin_name, image_source=0, num_samples=5
            )
            
            print()
            if success:
                print(f"[SUCCESS] {message}")
                print(f"[INFO] Admin {admin_name} (ID: {admin_id}) can now login via face!")
            else:
                print(f"[FAILED] {message}")
        
        elif choice == '2':
            # Authenticate
            print("\n--- AUTHENTICATE ADMIN ---")
            print("[INFO] Please look at your camera")
            print("[INFO] Authentication will timeout in 15 seconds\n")
            
            time.sleep(2)
            
            success, admin_id, admin_name, confidence, message = face_auth.authenticate_admin(
                image_source=0, threshold=0.6, timeout=15
            )
            
            print()
            if success:
                print("[SUCCESS] Authentication Successful!")
                print(f"  → Admin ID: {admin_id}")
                print(f"  → Admin Name: {admin_name}")
                print(f"  → Confidence: {confidence:.2f}%")
                print(f"  → Message: {message}")
            else:
                print(f"[FAILED] {message}")
        
        elif choice == '3':
            # List admins
            print("\n--- REGISTERED ADMINS ---")
            admins = face_auth.list_admins()
            
            if not admins:
                print("[INFO] No admins registered yet")
            else:
                print(f"\nFound {len(admins)} registered admin(s):\n")
                for admin in admins:
                    print(f"  • ID: {admin['admin_id']}")
                    print(f"    Name: {admin['name']}")
                    print(f"    Samples: {admin['num_samples']}")
                    print(f"    Registered: {admin['registered_at']}")
                    print()
        
        elif choice == '4':
            # Test detection
            print("\n--- TEST FACE DETECTION ---")
            print("[INFO] Press 'q' to quit\n")
            
            import cv2
            cap = cv2.VideoCapture(0)
            
            print("[INFO] Camera opened. Press 'q' to quit")
            
            while True:
                ret, frame = cap.read()
                if not ret:
                    break
                
                # Detect face
                face_box = face_auth.detect_face(frame)
                
                if face_box:
                    x, y, w, h = face_box
                    cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
                    cv2.putText(frame, "Face Detected", (x, y-10),
                              cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
                else:
                    cv2.putText(frame, "No face detected", (50, 50),
                              cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
                
                cv2.imshow('Face Detection Test - Press Q to quit', frame)
                
                if cv2.waitKey(1) & 0xFF == ord('q'):
                    break
            
            cap.release()
            cv2.destroyAllWindows()
            print("\n[INFO] Detection test completed")
        
        elif choice == '5':
            print("\n[INFO] Exiting demo...")
            break
        
        else:
            print("\n[ERROR] Invalid choice. Please select 1-5")
    
    print("\nThank you for using FaceNet Admin Authentication!")
    print("=" * 70)

if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n[INFO] Demo interrupted by user")
        sys.exit(0)
    except Exception as e:
        print(f"\n[ERROR] Unexpected error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
