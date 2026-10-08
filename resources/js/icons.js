import {
    createIcons, Link, ClipboardPaste, X, Download, Video, Film, Image, Images, Music, Menu,
    CircleAlert, LoaderCircle, CircleCheck, Clock, ShieldCheck, Zap, Smartphone, Archive,
    ChevronDown, Lock, Layers, Mail, Home, Info,
} from 'lucide';

const icons = {
    Link, ClipboardPaste, X, Download, Video, Film, Image, Images, Music, Menu,
    CircleAlert, LoaderCircle, CircleCheck, Clock, ShieldCheck, Zap, Smartphone, Archive,
    ChevronDown, Lock, Layers, Mail, Home, Info,
};

export function refreshIcons() {
    createIcons({ icons });
}
