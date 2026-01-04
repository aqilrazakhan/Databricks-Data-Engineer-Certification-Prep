# Integration Patterns

Patterns for integrating with document stores, file stores, and organizational tools.

## Table of Contents

1. Document Store Integration
2. File Store Integration
3. Organizational Tool Integration
4. Data Synchronization Patterns
5. Error Handling and Retry Logic

## 1. Document Store Integration

### Confluence Integration

**Use case**: Company wikis, knowledge bases

#### Authentication

```python
from atlassian import Confluence

confluence = Confluence(
    url='https://your-domain.atlassian.net',
    username='user@example.com',
    password='api_token',  # Use API token, not password
    cloud=True
)
```

#### Export Pages

```python
def export_confluence_pages(space_key):
    """Export all pages from a Confluence space."""
    pages = confluence.get_all_pages_from_space(
        space_key,
        start=0,
        limit=100,
        expand='body.storage,version'
    )

    documents = []
    for page in pages:
        doc = {
            'id': page['id'],
            'title': page['title'],
            'content': page['body']['storage']['value'],
            'url': f"{confluence.url}/wiki{page['_links']['webui']}",
            'last_updated': page['version']['when'],
            'metadata': {
                'source': 'confluence',
                'space': space_key,
                'author': page['version']['by']['displayName']
            }
        }
        documents.append(doc)

    return documents
```

#### Incremental Sync

```python
from datetime import datetime, timedelta

def sync_confluence_updates(space_key, since_date):
    """Sync only pages modified since a certain date."""
    cql = f'space = {space_key} AND lastModified >= "{since_date}"'

    results = confluence.cql(cql, limit=100)

    updated_pages = []
    for result in results['results']:
        page_id = result['content']['id']
        page = confluence.get_page_by_id(page_id, expand='body.storage,version')
        updated_pages.append(page)

    return updated_pages
```

### SharePoint Integration

**Use case**: Enterprise document management

#### Authentication

```python
from office365.sharepoint.client_context import ClientContext
from office365.runtime.auth.user_credential import UserCredential

site_url = 'https://your-tenant.sharepoint.com/sites/your-site'
credentials = UserCredential('user@tenant.onmicrosoft.com', 'password')

ctx = ClientContext(site_url).with_credentials(credentials)
```

#### Export Documents

```python
def export_sharepoint_documents(library_name):
    """Export documents from a SharePoint library."""
    # Get document library
    library = ctx.web.lists.get_by_title(library_name)

    # Get all items
    items = library.items.get().execute_query()

    documents = []
    for item in items:
        # Download file
        file = item.file.get().execute_query()
        file_content = file.read()

        doc = {
            'id': item.properties['Id'],
            'title': item.properties['FileLeafRef'],
            'content': file_content,
            'url': file.serverRelativeUrl,
            'last_updated': item.properties['Modified'],
            'metadata': {
                'source': 'sharepoint',
                'library': library_name,
                'author': item.properties['Author']['Title']
            }
        }
        documents.append(doc)

    return documents
```

### Notion Integration

**Use case**: Team documentation, project wikis

#### Authentication

```python
from notion_client import Client

notion = Client(auth="your_notion_api_token")
```

#### Export Pages

```python
def export_notion_database(database_id):
    """Export all pages from a Notion database."""
    results = notion.databases.query(database_id=database_id)

    documents = []
    for page in results['results']:
        # Get page content
        page_id = page['id']
        blocks = notion.blocks.children.list(page_id)

        # Extract text content from blocks
        content = extract_text_from_blocks(blocks['results'])

        doc = {
            'id': page_id,
            'title': extract_title(page),
            'content': content,
            'url': page['url'],
            'last_updated': page['last_edited_time'],
            'metadata': {
                'source': 'notion',
                'database': database_id
            }
        }
        documents.append(doc)

    return documents

def extract_text_from_blocks(blocks):
    """Extract text content from Notion blocks."""
    text = []
    for block in blocks:
        block_type = block['type']
        if block_type in ['paragraph', 'heading_1', 'heading_2', 'heading_3']:
            if 'rich_text' in block[block_type]:
                for rich_text in block[block_type]['rich_text']:
                    text.append(rich_text['plain_text'])
    return '\n'.join(text)
```

## 2. File Store Integration

### AWS S3 Integration

**Use case**: Cloud file storage

#### Authentication and Export

```python
import boto3
from io import BytesIO

s3 = boto3.client('s3',
    aws_access_key_id='your_access_key',
    aws_secret_access_key='your_secret_key',
    region_name='us-east-1'
)

def export_s3_documents(bucket_name, prefix=''):
    """Export documents from S3 bucket."""
    documents = []

    # List objects
    paginator = s3.get_paginator('list_objects_v2')
    pages = paginator.paginate(Bucket=bucket_name, Prefix=prefix)

    for page in pages:
        if 'Contents' not in page:
            continue

        for obj in page['Contents']:
            key = obj['Key']

            # Skip directories
            if key.endswith('/'):
                continue

            # Download object
            response = s3.get_object(Bucket=bucket_name, Key=key)
            content = response['Body'].read()

            doc = {
                'id': key,
                'title': key.split('/')[-1],
                'content': content,
                'url': f"s3://{bucket_name}/{key}",
                'last_updated': obj['LastModified'].isoformat(),
                'metadata': {
                    'source': 's3',
                    'bucket': bucket_name,
                    'size': obj['Size']
                }
            }
            documents.append(doc)

    return documents
```

### Network File Share (NAS/SMB)

**Use case**: On-premises file storage

#### Mount and Export

```python
import os
from pathlib import Path

def export_network_share(share_path, allowed_extensions=['.pdf', '.docx', '.txt']):
    """Export documents from network file share."""
    documents = []

    # Walk directory tree
    for root, dirs, files in os.walk(share_path):
        for file in files:
            # Filter by extension
            if not any(file.endswith(ext) for ext in allowed_extensions):
                continue

            file_path = Path(root) / file

            # Read file
            with open(file_path, 'rb') as f:
                content = f.read()

            # Get metadata
            stat = file_path.stat()

            doc = {
                'id': str(file_path),
                'title': file,
                'content': content,
                'path': str(file_path),
                'last_updated': datetime.fromtimestamp(stat.st_mtime).isoformat(),
                'metadata': {
                    'source': 'network_share',
                    'size': stat.st_size,
                    'extension': file_path.suffix
                }
            }
            documents.append(doc)

    return documents
```

### Google Drive Integration

**Use case**: Cloud collaboration platform

#### Authentication and Export

```python
from googleapiclient.discovery import build
from google.oauth2 import service_account

SCOPES = ['https://www.googleapis.com/auth/drive.readonly']
SERVICE_ACCOUNT_FILE = 'path/to/service-account-key.json'

credentials = service_account.Credentials.from_service_account_file(
    SERVICE_ACCOUNT_FILE, scopes=SCOPES
)

drive_service = build('drive', 'v3', credentials=credentials)

def export_google_drive_folder(folder_id):
    """Export documents from a Google Drive folder."""
    documents = []

    # Query files in folder
    query = f"'{folder_id}' in parents and trashed=false"
    results = drive_service.files().list(
        q=query,
        fields="files(id, name, mimeType, modifiedTime, webViewLink)"
    ).execute()

    files = results.get('files', [])

    for file in files:
        # Export file content based on MIME type
        if file['mimeType'] == 'application/vnd.google-apps.document':
            # Google Doc - export as plain text
            content = drive_service.files().export(
                fileId=file['id'],
                mimeType='text/plain'
            ).execute()
        else:
            # Other files - download directly
            content = drive_service.files().get_media(
                fileId=file['id']
            ).execute()

        doc = {
            'id': file['id'],
            'title': file['name'],
            'content': content,
            'url': file['webViewLink'],
            'last_updated': file['modifiedTime'],
            'metadata': {
                'source': 'google_drive',
                'mime_type': file['mimeType']
            }
        }
        documents.append(doc)

    return documents
```

## 3. Organizational Tool Integration

### Jira Integration

**Use case**: Project management, tickets, issues

#### Authentication and Export

```python
from jira import JIRA

jira = JIRA(
    server='https://your-domain.atlassian.net',
    basic_auth=('user@example.com', 'api_token')
)

def export_jira_issues(project_key, since_date=None):
    """Export issues from a Jira project."""
    # Build JQL query
    jql = f'project = {project_key}'
    if since_date:
        jql += f' AND updated >= "{since_date}"'

    # Fetch issues
    issues = jira.search_issues(jql, maxResults=1000, expand='changelog')

    documents = []
    for issue in issues:
        # Combine title, description, and comments
        content_parts = [
            f"Title: {issue.fields.summary}",
            f"Description: {issue.fields.description or 'No description'}",
            "\nComments:"
        ]

        for comment in issue.fields.comment.comments:
            content_parts.append(f"- {comment.author.displayName}: {comment.body}")

        doc = {
            'id': issue.key,
            'title': f"{issue.key}: {issue.fields.summary}",
            'content': '\n'.join(content_parts),
            'url': f"{jira.server_url}/browse/{issue.key}",
            'last_updated': issue.fields.updated,
            'metadata': {
                'source': 'jira',
                'project': project_key,
                'status': issue.fields.status.name,
                'priority': issue.fields.priority.name if issue.fields.priority else None
            }
        }
        documents.append(doc)

    return documents
```

### Slack Integration

**Use case**: Team communications, channel archives

#### Authentication and Export

```python
from slack_sdk import WebClient

slack_client = WebClient(token='your_slack_bot_token')

def export_slack_channel(channel_id, since_timestamp=None):
    """Export messages from a Slack channel."""
    messages = []

    # Fetch conversation history
    result = slack_client.conversations_history(
        channel=channel_id,
        oldest=since_timestamp,
        limit=1000
    )

    for message in result['messages']:
        # Skip bot messages if desired
        if message.get('subtype') == 'bot_message':
            continue

        # Get user info
        user_id = message.get('user')
        user_info = slack_client.users_info(user=user_id) if user_id else None

        # Handle threaded messages
        thread_messages = []
        if message.get('thread_ts'):
            thread_result = slack_client.conversations_replies(
                channel=channel_id,
                ts=message['thread_ts']
            )
            thread_messages = [m['text'] for m in thread_result['messages'][1:]]

        content = message['text']
        if thread_messages:
            content += "\n\nThread:\n" + "\n".join(thread_messages)

        doc = {
            'id': message['ts'],
            'title': f"Slack message from {user_info['user']['real_name'] if user_info else 'Unknown'}",
            'content': content,
            'timestamp': message['ts'],
            'metadata': {
                'source': 'slack',
                'channel': channel_id,
                'user': user_info['user']['real_name'] if user_info else None
            }
        }
        messages.append(doc)

    return messages
```

### Email Integration (IMAP)

**Use case**: Email archives, support tickets

#### Authentication and Export

```python
import imaplib
import email
from email.header import decode_header

def export_emails(imap_server, username, password, mailbox='INBOX', since_date=None):
    """Export emails from IMAP mailbox."""
    # Connect to IMAP server
    mail = imaplib.IMAP4_SSL(imap_server)
    mail.login(username, password)
    mail.select(mailbox)

    # Search criteria
    if since_date:
        criteria = f'(SINCE "{since_date}")'
    else:
        criteria = 'ALL'

    # Search emails
    status, message_ids = mail.search(None, criteria)

    documents = []
    for msg_id in message_ids[0].split():
        # Fetch email
        status, msg_data = mail.fetch(msg_id, '(RFC822)')
        raw_email = msg_data[0][1]

        # Parse email
        msg = email.message_from_bytes(raw_email)

        # Extract subject
        subject = decode_header(msg['Subject'])[0][0]
        if isinstance(subject, bytes):
            subject = subject.decode()

        # Extract body
        body = extract_email_body(msg)

        doc = {
            'id': msg_id.decode(),
            'title': subject,
            'content': body,
            'from': msg['From'],
            'date': msg['Date'],
            'metadata': {
                'source': 'email',
                'mailbox': mailbox,
                'to': msg['To'],
                'cc': msg.get('Cc', '')
            }
        }
        documents.append(doc)

    mail.close()
    mail.logout()

    return documents

def extract_email_body(msg):
    """Extract text body from email message."""
    if msg.is_multipart():
        for part in msg.walk():
            content_type = part.get_content_type()
            if content_type == 'text/plain':
                return part.get_payload(decode=True).decode()
    else:
        return msg.get_payload(decode=True).decode()
    return ""
```

## 4. Data Synchronization Patterns

### Pattern: Full Sync

**When to use**: Initial load, small datasets, simple setup

```python
def full_sync(source_connector, vector_db):
    """Perform full synchronization of all documents."""
    # 1. Fetch all documents from source
    documents = source_connector.export_all()

    # 2. Clear existing data in vector DB
    vector_db.clear_collection()

    # 3. Process and ingest all documents
    for doc in documents:
        chunks = chunk_document(doc['content'])
        embeddings = embed_chunks(chunks)
        vector_db.add_documents(embeddings, chunks, doc['metadata'])

    return {'synced': len(documents)}
```

### Pattern: Incremental Sync

**When to use**: Large datasets, regular updates, efficiency needed

```python
from datetime import datetime

def incremental_sync(source_connector, vector_db, last_sync_time):
    """Sync only documents modified since last sync."""
    # 1. Fetch only updated/new documents
    updated_docs = source_connector.export_since(last_sync_time)

    # 2. For each updated document
    for doc in updated_docs:
        # Remove old version
        vector_db.delete_by_id(doc['id'])

        # Add new version
        chunks = chunk_document(doc['content'])
        embeddings = embed_chunks(chunks)
        vector_db.add_documents(embeddings, chunks, doc['metadata'])

    # 3. Update last sync timestamp
    return {
        'synced': len(updated_docs),
        'last_sync_time': datetime.now().isoformat()
    }
```

### Pattern: Event-Driven Sync

**When to use**: Real-time updates, webhook support available

```python
from fastapi import FastAPI

app = FastAPI()

@app.post("/webhook/document-updated")
async def handle_document_update(payload: dict):
    """Handle webhook notification of document update."""
    doc_id = payload['document_id']

    # Fetch updated document
    doc = source_connector.get_document(doc_id)

    # Remove old version
    vector_db.delete_by_id(doc_id)

    # Add new version
    chunks = chunk_document(doc['content'])
    embeddings = embed_chunks(chunks)
    vector_db.add_documents(embeddings, chunks, doc['metadata'])

    return {'status': 'synced', 'document_id': doc_id}
```

### Pattern: Scheduled Sync

**When to use**: Regular updates, batch processing preferred

```python
from apscheduler.schedulers.background import BackgroundScheduler
from datetime import datetime, timedelta

scheduler = BackgroundScheduler()

def scheduled_sync():
    """Run incremental sync on schedule."""
    # Load last sync time
    last_sync = load_last_sync_time()

    # Perform incremental sync
    result = incremental_sync(source_connector, vector_db, last_sync)

    # Save new sync time
    save_last_sync_time(datetime.now())

    print(f"Sync completed: {result}")

# Schedule to run every 6 hours
scheduler.add_job(scheduled_sync, 'interval', hours=6)
scheduler.start()
```

## 5. Error Handling and Retry Logic

### Retry Decorator

```python
import time
from functools import wraps

def retry(max_attempts=3, delay=1, backoff=2, exceptions=(Exception,)):
    """Retry decorator with exponential backoff."""
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            attempt = 1
            current_delay = delay

            while attempt <= max_attempts:
                try:
                    return func(*args, **kwargs)
                except exceptions as e:
                    if attempt == max_attempts:
                        raise
                    print(f"Attempt {attempt} failed: {e}. Retrying in {current_delay}s...")
                    time.sleep(current_delay)
                    current_delay *= backoff
                    attempt += 1

        return wrapper
    return decorator

# Usage
@retry(max_attempts=3, delay=2, exceptions=(ConnectionError, TimeoutError))
def fetch_confluence_page(page_id):
    return confluence.get_page_by_id(page_id)
```

### Error Logging and Alerting

```python
import logging

logger = logging.getLogger(__name__)

def safe_document_sync(doc_id):
    """Sync document with comprehensive error handling."""
    try:
        doc = source_connector.get_document(doc_id)
        chunks = chunk_document(doc['content'])
        embeddings = embed_chunks(chunks)
        vector_db.add_documents(embeddings, chunks, doc['metadata'])

        logger.info(f"Successfully synced document {doc_id}")
        return {'status': 'success', 'document_id': doc_id}

    except ConnectionError as e:
        logger.error(f"Connection error syncing {doc_id}: {e}")
        # Queue for retry
        retry_queue.add(doc_id)
        return {'status': 'connection_error', 'document_id': doc_id}

    except ValueError as e:
        logger.error(f"Invalid document format {doc_id}: {e}")
        # Log for manual review
        error_log.add({'document_id': doc_id, 'error': str(e)})
        return {'status': 'invalid_format', 'document_id': doc_id}

    except Exception as e:
        logger.exception(f"Unexpected error syncing {doc_id}: {e}")
        # Alert on call
        send_alert(f"Critical error syncing document: {doc_id}")
        return {'status': 'critical_error', 'document_id': doc_id}
```

### Rate Limiting

```python
import time
from collections import deque

class RateLimiter:
    def __init__(self, max_calls, period):
        """Rate limiter using sliding window.

        Args:
            max_calls: Maximum number of calls allowed
            period: Time period in seconds
        """
        self.max_calls = max_calls
        self.period = period
        self.calls = deque()

    def __call__(self, func):
        def wrapper(*args, **kwargs):
            now = time.time()

            # Remove old calls outside the window
            while self.calls and self.calls[0] < now - self.period:
                self.calls.popleft()

            # Check if limit reached
            if len(self.calls) >= self.max_calls:
                sleep_time = self.period - (now - self.calls[0])
                if sleep_time > 0:
                    print(f"Rate limit reached. Sleeping for {sleep_time:.2f}s")
                    time.sleep(sleep_time)
                    return wrapper(*args, **kwargs)

            # Record this call
            self.calls.append(now)

            return func(*args, **kwargs)

        return wrapper

# Usage: Max 10 calls per minute
@RateLimiter(max_calls=10, period=60)
def fetch_api_data(endpoint):
    return api_client.get(endpoint)
```

## Integration Checklist

Before integrating a new data source:

- [ ] Authentication configured (API keys, tokens, credentials)
- [ ] Rate limiting implemented
- [ ] Error handling and retries in place
- [ ] Logging configured
- [ ] Incremental sync strategy defined
- [ ] Metadata extraction mapped
- [ ] Content cleaning/normalization implemented
- [ ] Test with sample data
- [ ] Document sync frequency requirements
- [ ] Identify and handle edge cases (deleted docs, permissions, etc.)
